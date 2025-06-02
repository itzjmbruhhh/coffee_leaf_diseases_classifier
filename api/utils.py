import tensorflow as tf
from tensorflow.keras.preprocessing.image import img_to_array
from tensorflow.keras.applications.mobilenet_v3 import preprocess_input
from tensorflow.keras.models import Model
import cv2
from PIL import Image
import numpy as np
from io import BytesIO
import os


def generate_heatmap(image_path, model, output_dir="heatmap") -> str:
    """
    Generates and saves a heatmap showing which part of the image
    influenced the prediction the most.
    """
    # Load and preprocess image for model
    img_bgr = cv2.imread(image_path)  # Load image in BGR format
    img_rgb = cv2.cvtColor(img_bgr, cv2.COLOR_BGR2RGB)  # Convert to RGB
    img_resized = cv2.resize(img_rgb, (224, 224))  # Resize to model input size
    
    # Expand dimensions and preprocess
    X = np.expand_dims(img_resized, axis=0).astype(np.float32)
    X = preprocess_input(X)

    # Get intermediate feature maps and final prediction
    conv_output = model.get_layer("out_relu").output  # Feature map layer
    pred_output = model.get_layer("predictions").output  # Output layer
    model_2 = Model(model.input, outputs=[conv_output, pred_output])
    conv, pred = model_2.predict(X)

    # Get class index with highest prediction score
    target = np.argmax(pred[0])
    
    # Get weights for that class from final dense layer
    w, b = model.get_layer("predictions").weights
    weights = w[:, target].numpy()

    # Compute weighted sum of feature maps
    heatmap = conv[0] @ weights
    heatmap = np.maximum(heatmap, 0)  # Apply ReLU
    heatmap /= np.max(heatmap)  # Normalize

    # Resize heatmap to match original image
    heatmap_resized = cv2.resize(heatmap, (img_bgr.shape[1], img_bgr.shape[0]))

    # Apply color mapping to heatmap
    heatmap_colored = cv2.applyColorMap(np.uint8(255 * heatmap_resized), cv2.COLORMAP_JET)

    # Overlay heatmap on original image
    superimposed_img = cv2.addWeighted(img_bgr, 0.6, heatmap_colored, 0.4, 0)

    # Save resulting image
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
    elif predicted_class == "Not Coffee" and confidence < 80:
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
