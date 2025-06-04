from tensorflow.keras.models import load_model
import os

from fastapi import FastAPI, UploadFile, HTTPException
import uvicorn
import uuid

from utils import generate_heatmap, coffee_or_not, predict_image, read_file_as_image

# Initialize FastAPI app
app = FastAPI()

# Load pre-trained models
main_model_path = "api/model.keras"
second_model_path = "api/coffee_or_not.keras"
MAIN_MODEL = load_model(main_model_path)      # Disease classification model
SECOND_MODEL = load_model(second_model_path)    # Coffee leaf detector model

# Define class labels for each model
CLASS_NAMES = ['Cercospora', 'Healthy', 'Miner', 'Phoma', 'Rust']
CLASS_NAMES2 = ["Coffee", "Not Coffee"]

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
    if confidence < 70.0:
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