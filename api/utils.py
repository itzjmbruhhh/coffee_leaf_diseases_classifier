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
    Generates and saves a Grad-CAM heatmap using OpenCV.
    """

    # Load and preprocess image
    img_bgr = cv2.imread(image_path)
    img_rgb = cv2.cvtColor(img_bgr, cv2.COLOR_BGR2RGB)
    img_resized = cv2.resize(img_rgb, (224, 224))

    X = tf.convert_to_tensor(np.expand_dims(img_resized, axis=0).astype(np.float32))
    X = preprocess_input(X)

    # Create grad model
    grad_model = Model(
        inputs=model.input,
        outputs=[model.get_layer(last_conv_layer_name).output, model.output]
    )

    # Gradient tape context
    with tf.GradientTape() as tape:
        conv_outputs, predictions = grad_model(X)
        pred_index = tf.argmax(predictions[0])
        class_channel = predictions[:, pred_index]

    # Compute gradients
    grads = tape.gradient(class_channel, conv_outputs)
    pooled_grads = tf.reduce_mean(grads, axis=(0, 1, 2))  # shape: (channels,)

    conv_outputs = conv_outputs[0].numpy()
    pooled_grads = pooled_grads.numpy()

    # Weight channels
    for i in range(pooled_grads.shape[0]):
        conv_outputs[:, :, i] *= pooled_grads[i]

    # Compute heatmap
    heatmap = np.mean(conv_outputs, axis=-1)
    heatmap = np.maximum(heatmap, 0)
    heatmap /= np.max(heatmap + 1e-8)  # Prevent divide-by-zero

    # Resize heatmap to original image size
    heatmap_resized = cv2.resize(heatmap, (img_bgr.shape[1], img_bgr.shape[0]))
    heatmap_colored = cv2.applyColorMap(np.uint8(255 * heatmap_resized), cv2.COLORMAP_JET)

    # Superimpose heatmap on image
    superimposed_img = cv2.addWeighted(img_bgr, 0.6, heatmap_colored, 0.4, 0)

    # Save and return
    os.makedirs(output_dir, exist_ok=True)
    save_path = os.path.join(output_dir, f"heatmap_{os.path.basename(image_path)}")
    cv2.imwrite(save_path, superimposed_img)

    return save_path


def coffee_or_not(model, img, class_names):
    """
    Checks if the input image is likely a coffee leaf.
    Accepts 'Coffee' if confidence is high,
    and also passes borderline 'Not Coffee' predictions with low confidence.
    """
    img = img.resize((224, 224))
    img_array = img_to_array(img)
    img_array = tf.expand_dims(img_array, 0)
    predictions = model.predict(img_array)

    predicted_class = class_names[np.argmax(predictions[0])]
    confidence = round(100 * np.max(predictions[0]), 2)

    # Accept if confidently Coffee or borderline Not Coffee
    if predicted_class == "Coffee":
        return True
    elif predicted_class == "Not Coffee" and confidence <= 75:
        return True
    else:
        return False

def predict_image(model, img, class_names):
    """
    Predicts the class of the image using the disease classification model.
    Returns:
    - Predicted class name
    - Confidence
    - String of all class probabilities
    """
    img = img.resize((224, 224))
    img_array = img_to_array(img)
    img_array = tf.expand_dims(img_array, 0)
    predictions = model.predict(img_array)

    probs = predictions[0]
    predicted_class = class_names[np.argmax(predictions[0])]
    confidence = round(100 * np.max(predictions[0]), 2)

    # Generate readable string of probabilities
    prob_percentages = [f"{round(100 * p)}% {name}" for p, name in zip(probs, class_names)]
    prob_str = ", ".join(prob_percentages)

    return predicted_class, confidence, prob_str

def read_file_as_image(data) -> np.ndarray:
    """
    Converts byte stream image into a PIL image and ensures it's RGB.
    """
    image = Image.open(BytesIO(data))
    if image.mode == 'RGBA':
        image = image.convert('RGB')  # Remove alpha channel if exists
    return image
