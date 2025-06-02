import tensorflow as tf
from tensorflow.keras.preprocessing.image import img_to_array
from tensorflow.keras.models import load_model
import cv2
from tensorflow.keras.applications.mobilenet_v3 import preprocess_input
from tensorflow.keras.models import Model
import os

from fastapi import FastAPI, UploadFile, HTTPException
from PIL import Image
import numpy as np
from io import BytesIO
import uvicorn
import uuid

# Initialize FastAPI app
app = FastAPI()

# Load pre-trained models
main_model_path = "api/model.keras"
second_model_path = "api/coffee_or_not.keras"
MAIN_MODEL = load_model(main_model_path)      # Disease classification model
SECOND_MODEL = load_model(second_model_path)    # Coffee leaf detector model

# Define class labels for each model
CLASS_NAMES = ["Healthy", "Miner", "Phoma", "Rust"]
CLASS_NAMES2 = ["Coffee", "Not Coffee"]

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

@app.post("/predict")
async def predict(file: UploadFile):
    """
    FastAPI endpoint for image prediction.
    Performs coffee leaf check, predicts class, and generates heatmap.
    """
    # Read and process uploaded image
    image_bytes = await file.read()
    image = read_file_as_image(image_bytes)

    # Check if the image is likely a coffee leaf
    if not coffee_or_not(SECOND_MODEL, image, CLASS_NAMES2):
        raise HTTPException(status_code=400, detail="Image is unlikely to be a coffee leaf.")
    
    # Save temporary copy for heatmap generation
    temp_input_path = f"temp_{uuid.uuid4().hex}.jpg"
    image.save(temp_input_path)

    # Make prediction
    prediction, confidence, all_probs = predict_image(MAIN_MODEL, image, CLASS_NAMES)

    # Reject low-confidence results
    if confidence < 75.0:
        os.remove(temp_input_path)
        raise HTTPException(status_code=422, detail="Failed to classify image.")

    # Generate heatmap
    heatmap_path = generate_heatmap(temp_input_path, MAIN_MODEL)

    # Clean up temporary file
    os.remove(temp_input_path)

    return {
        "prediction": prediction,
        "confidence": round(float(confidence)),
        "probabilities": all_probs,
        "heatmap_path": heatmap_path  # Can be rendered or downloaded via frontend
    }

# Start server when run as script
if __name__ == "__main__":
    uvicorn.run(app, host='localhost', port=8000)