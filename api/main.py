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

app = FastAPI()

model_path = "api/model.keras"
MODEL = load_model(model_path)
CLASS_NAMES = ["Healthy", "Miner", "Phoma", "Rust"]

def generate_heatmap(image_path, model, output_dir="heatmap") -> str:
    # Read and preprocess image
    img_bgr = cv2.imread(image_path)  # OpenCV loads in BGR
    img_rgb = cv2.cvtColor(img_bgr, cv2.COLOR_BGR2RGB)  # Convert to RGB for model
    img_resized = cv2.resize(img_rgb, (224, 224))
    
    X = np.expand_dims(img_resized, axis=0).astype(np.float32)
    X = preprocess_input(X)

    # Get intermediate outputs
    conv_output = model.get_layer("out_relu").output
    pred_output = model.get_layer("predictions").output
    model_2 = Model(model.input, outputs=[conv_output, pred_output])
    conv, pred = model_2.predict(X)

    # Get class-specific weights and apply
    target = np.argmax(pred[0])
    w, b = model.get_layer("predictions").weights
    weights = w[:, target].numpy()
    
    heatmap = conv[0] @ weights
    heatmap = np.maximum(heatmap, 0)
    heatmap /= np.max(heatmap)

    # Resize heatmap to match original image size
    heatmap_resized = cv2.resize(heatmap, (img_bgr.shape[1], img_bgr.shape[0]))

    # Convert to colormap
    heatmap_colored = cv2.applyColorMap(np.uint8(255 * heatmap_resized), cv2.COLORMAP_JET)

    # Overlay the heatmap on the original BGR image
    superimposed_img = cv2.addWeighted(img_bgr, 0.6, heatmap_colored, 0.4, 0)

    # Save output
    os.makedirs(output_dir, exist_ok=True)
    save_path = os.path.join(output_dir, f"heatmap_{os.path.basename(image_path)}")
    cv2.imwrite(save_path, superimposed_img)  # Save in BGR format for proper viewing

    return save_path


def predict_image(model, img, class_names):
    """ This function is resonsible for model prediction """
    img = img.resize((224, 224)) # Resizes image based on accepted model input
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
    # Read and validate
    image_bytes = await file.read()
    image = read_file_as_image(image_bytes)
    
    if not isLeaf(image):
        raise HTTPException(status_code=400, detail="Image is unlikely to be a leaf.")
    
    # Save uploaded image temporarily
    temp_input_path = f"temp_{uuid.uuid4().hex}.jpg"
    image.save(temp_input_path)

    # Predict
    prediction, confidence, all_probs = predict_image(MODEL, image, CLASS_NAMES)

    if confidence < 75.0:
        os.remove(temp_input_path)
        raise HTTPException(status_code=422, detail="Failed to classify image.")
    
    # Generate and save heatmap
    heatmap_path = generate_heatmap(temp_input_path, MODEL)
    
    # Optionally delete the temp image
    os.remove(temp_input_path)

    return {
        "prediction": prediction,
        "confidence": round(float(confidence)),
        "probabilities": all_probs,
        "heatmap_path": heatmap_path  # Can be returned as relative or URL
    }
    
if __name__ == "__main__":
    uvicorn.run(app, host='localhost', port=8000)