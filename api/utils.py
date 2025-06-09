import tensorflow as tf
from tensorflow.keras.preprocessing.image import img_to_array
from tensorflow.keras.applications.mobilenet_v2 import preprocess_input
from tensorflow.keras.models import Model
import cv2
from PIL import Image
import numpy as np
from io import BytesIO
import os

def generate_heatmap(image_path, model, last_conv_layer_name="Conv_1", output_dir="heatmap") -> str:
    """
    Generates and saves a Grad-CAM heatmap for the given image and model.
    
    Args:
        image_path (str): Path to the input image.
        model (tf.keras.Model): Trained classification model.
        last_conv_layer_name (str): Name of the last convolutional layer.
        output_dir (str): Directory to save the output heatmap.
    
    Returns:
        str: Path to the saved heatmap image.
    """
    # Load image using OpenCV (BGR format)
    img_bgr = cv2.imread(image_path)
    img_rgb = cv2.cvtColor(img_bgr, cv2.COLOR_BGR2RGB)  # Convert to RGB
    img_resized = cv2.resize(img_rgb, (224, 224))       # Resize for model input

    # Prepare image tensor for model
    X = tf.convert_to_tensor(np.expand_dims(img_resized, axis=0).astype(np.float32))
    X = preprocess_input(X)  # Preprocess input for MobileNetV2

    # Create a model that maps input to the activations of the last conv layer and output
    grad_model = Model(
        inputs=model.input,
        outputs=[model.get_layer(last_conv_layer_name).output, model.output]
    )

    # Compute gradients of top predicted class with respect to conv layer output
    with tf.GradientTape() as tape:
        conv_outputs, predictions = grad_model(X)
        pred_index = tf.argmax(predictions[0])
        class_channel = predictions[:, pred_index]

    # Calculate gradients
    grads = tape.gradient(class_channel, conv_outputs)
    pooled_grads = tf.reduce_mean(grads, axis=(0, 1, 2))  # Average over width and height

    conv_outputs = conv_outputs[0].numpy()    # Feature maps
    pooled_grads = pooled_grads.numpy()       # Gradient weights

    # Multiply each feature map by corresponding gradient weight
    for i in range(pooled_grads.shape[0]):
        conv_outputs[:, :, i] *= pooled_grads[i]

    # Compute the heatmap by averaging along the channel axis
    heatmap = np.mean(conv_outputs, axis=-1)
    heatmap = np.maximum(heatmap, 0)  # ReLU to zero out negatives
    heatmap /= np.max(heatmap + 1e-8)  # Normalize to [0,1]

    # Resize heatmap to original image size and apply color map
    heatmap_resized = cv2.resize(heatmap, (img_bgr.shape[1], img_bgr.shape[0]))
    heatmap_colored = cv2.applyColorMap(np.uint8(255 * heatmap_resized), cv2.COLORMAP_JET)

    # Blend original image with heatmap
    superimposed_img = cv2.addWeighted(img_bgr, 0.6, heatmap_colored, 0.4, 0)

    # Save final heatmap image
    os.makedirs(output_dir, exist_ok=True)
    save_path = os.path.join(output_dir, f"heatmap_{os.path.basename(image_path)}")
    cv2.imwrite(save_path, superimposed_img)

    return save_path


def coffee_or_not(model, img, class_names):
    """
    Determines if the image is likely a coffee leaf using a binary classifier.
    
    Args:
        model (tf.keras.Model): Binary classifier model (coffee vs not coffee).
        img (PIL.Image): Input image.
        class_names (list): List of class labels (e.g., ["Coffee", "Not Coffee"]).
    
    Returns:
        bool: True if image is coffee or uncertain Not Coffee, else False.
    """
    img = img.resize((224, 224))  # Resize to model input size
    img_array = img_to_array(img)
    img_array = tf.expand_dims(img_array, 0)  # Add batch dimension

    predictions = model.predict(img_array)
    predicted_class = class_names[np.argmax(predictions[0])]
    confidence = round(100 * np.max(predictions[0]), 2)

    # Accept if confidently Coffee or uncertain Not Coffee
    if predicted_class == "Coffee":
        return True
    elif predicted_class == "Not Coffee" and confidence <= 75:
        return True
    else:
        return False


def predict_image(model, img, class_names):
    """
    Predicts the disease class of the image using the main classification model.
    
    Args:
        model (tf.keras.Model): Multi-class classification model.
        img (PIL.Image): Input image.
        class_names (list): List of class labels for classification.
    
    Returns:
        tuple: (predicted class name, confidence %, formatted class probabilities)
    """
    img = img.resize((224, 224))  # Resize to expected input shape
    img_array = img_to_array(img)
    img_array = tf.expand_dims(img_array, 0)  # Add batch dimension

    predictions = model.predict(img_array)

    probs = predictions[0]  # Raw output probabilities
    predicted_class = class_names[np.argmax(probs)]
    confidence = round(100 * np.max(probs), 2)

    # Format probabilities for all classes
    prob_percentages = [f"{round(100 * p)}% {name}" for p, name in zip(probs, class_names)]
    prob_str = ", ".join(prob_percentages)

    return predicted_class, confidence, prob_str


def read_file_as_image(data) -> np.ndarray:
    """
    Converts image bytes to a PIL RGB image.
    
    Args:
        data (bytes): Byte stream of an image file.
    
    Returns:
        PIL.Image: RGB formatted image.
    """
    image = Image.open(BytesIO(data))
    
    # Ensure image is in RGB mode (convert from RGBA if needed)
    if image.mode == 'RGBA':
        image = image.convert('RGB')
        
    return image
