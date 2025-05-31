import tensorflow as tf
from tensorflow.keras.preprocessing.image import img_to_array
from tensorflow.keras.models import load_model
import cv2
from scipy.ndimage import zoom
from tensorflow.keras.applications.mobilenet_v2 import preprocess_input, decode_predictions
from tensorflow.keras.models import Model

from fastapi import FastAPI, UploadFile, HTTPException
from PIL import Image
import numpy as np
from io import BytesIO
import uvicorn

app = FastAPI()

model_path = "api/model.keras"
MODEL = load_model(model_path)
CLASS_NAMES = ["Healthy", "Miner", "Phoma", "Rust"]

def heatmap(image, model):
    """ This function is responsible for returning a heatmap from the predicted image result """
    img = cv2.imread(image)
    img = cv2.cvtColor(img, cv2.COLOR_RGB2BGR)
    img = cv2.resize(img, (224, 224))
    X = np.expand_dims(img, axis=0).astype(np.float32)
    X = preprocess_input(X)

def predict_image(model, img, class_names):
    """ This function is resonsible for model prediction """
    img = img.resize((299, 299)) # Resizes image based on accepted model input
    img_array = img_to_array(img) # Turns image to numpy array
    img_array = tf.expand_dims(img_array, 0) # Creates batch expansion
    predictions = model.predict(img_array) # Predict function
    
    probs = predictions[0]
    predicted_class = class_names[np.argmax(predictions[0])]
    confidence = round(100 * np.max(predictions[0]), 2)
    
    #Format full body string
    prob_percentages = [f"{round(100 * p)}% {name}" for p, name in zip(probs, class_names)]
    prob_str = ", ".join(prob_percentages)
    
    return predicted_class, confidence, prob_str

def read_file_as_image(data) -> np.ndarray:
    """ This function converts image to numpy array """
    image = Image.open(BytesIO(data)) # Use pillow to read images formatted as bytes
    if image.mode == 'RGBA':
        image = image.convert('RGB') # Remove alpha channel
    
    return image

def isLeaf(image: Image.Image, green_threshold=0.1) -> bool:
    """ 
    Enhanced heuristic to determine if an image likely contains a leaf,
    allowing for discoloration (e.g., yellow, brown, diseased areas).
    """
    
    image = image.resize((150, 150))
    img_array = np.array(image.convert('RGB'))
    r, g, b = img_array[:,:,0], img_array[:,:,1], img_array[:,:,2]
    
    # Define a broader image of acceptable "leaf-like" pixels
    green_pixels = (
        (g > 60) & # still needs some green
        (g >= r - 15) & # not dominated by red (brownish is allowed)
        (g >= b - 10) # not dominated by blue (keeps out blue sky)
    )
    
    green_ratio = np.sum(green_pixels) / green_pixels.size
    
    return green_ratio >= green_threshold

@app.post("/predict")
async def predict(file: UploadFile):
    """ This is the predict api/function that we call """
    image = read_file_as_image(await file.read())
    
    if not isLeaf(image):
        raise HTTPException(status_code=400, detail="Image is unlikely to be a leaf. Please Upload a valid leaf image.")
    
    prediction, confidence, all_probs = predict_image(MODEL, image, CLASS_NAMES)
    
    if confidence < 75.0:
        raise HTTPException(status_code=422, detail="Failed to classify image. Please upload another one.")
    
    return {
        "prediction" : prediction,
        "confidence" : round(float(confidence)),
        "probabilities" : all_probs
    }
    
if __name__ == "__main__":
    uvicorn.run(app, host='localhost', port=8000)