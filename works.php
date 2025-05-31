<?php

session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login/login");
    exit();
}


?>

<!DOCTYPE html>
<html lang="en">

<head><?php include "components/head.php"; ?> <title>Leaf It Up to Me || How it works?</title> </head>

<body>

    <header><?php include "components/nav_works.php"; ?></header>

    <main>
        <section>

            <!-- HOW IT WORKS SECTION -->
            <div class="container">
                <div class="how-it-works-section">
                    <h2>How 'Leaf It Up to Me' Works</h2>

                    <div class="how-it-works-steps">

                        <div class="step">
                            <h3>1. Upload a Leaf Image</h3>
                            <p>
                                Users begin by uploading a photo of a coffee leaf using the provided interface.
                                The image must be clear and taken in good lighting to ensure accurate results.
                            </p>
                        </div>

                        <div class="step">
                            <h3>2. Image Processing and Classification</h3>
                            <p>
                                The uploaded image is processed by a <strong>highly accurate Convolutional Neural
                                    Network (CNN) model</strong>
                                trained using <strong>TensorFlow</strong>. The model analyzes the leaf and determines
                                its health status—
                                classifying it as <strong>Healthy</strong>, <strong>Miner</strong>,
                                <strong>Phoma</strong>, or <strong>Rust</strong>. Lean more about Tensorflow and Deeplearning <a href="https://www.tensorflow.org/api_docs" target="_blank">here</a>.
                            </p>
                        </div>

                        <div class="step">
                            <h3>3. Storing Results</h3>
                            <p>
                                Once classified, the system stores the following data into the database:
                            </p>
                            <ul>
                                <li><strong>Uploaded Image</strong></li>
                                <li><strong>Predicted Class</strong> (e.g., Rust, Healthy, etc.)</li>
                                <li><strong>Date and Time</strong> the image was processed</li>
                                <li><strong>Confidence Level</strong> of the prediction (e.g., 97.2%)</li>
                            </ul>
                        </div>

                        <div class="step">
                            <h3>4. Displaying the Results</h3>
                            <p>
                                The results, including the image and its classification with the confidence score, are
                                shown on the screen
                                for the user to review. This empowers farmers and agriculturists to take informed
                                actions based on early detection.
                            </p>
                        </div>

                    </div>
                </div>
            </div>

            <div class="container">
                <div class="disease-info-section">
                    <h2>Coffee Leaf Diseases</h2>

                    <div class="disease-cards">
                        <div class="disease-card">
                            <img src="components/images/healthy.jpg" alt="Healthy Leaf">
                            <h3>Healthy</h3>
                            <p>Bright green leaves with no visible damage or discoloration. Indicates a well-nourished,
                                disease-free coffee plant.</p>
                        </div>
                        <div class="disease-card">
                            <img src="components/images/miner.jpg" alt="Miner Leaf">
                            <h3>Miner</h3>
                            <p>Irregular trails or blotches caused by leaf miner larvae feeding between leaf layers. Can
                                reduce photosynthesis if untreated.</p>
                        </div>
                        <div class="disease-card">
                            <img src="components/images/phoma.jpg" alt="Phoma Leaf">
                            <h3>Phoma</h3>
                            <p>Dark, sunken spots with yellow halos. Caused by fungal infection and can lead to leaf
                                drop and defoliation.</p>
                        </div>
                        <div class="disease-card">
                            <img src="components/images/rust.jpg" alt="Rust Leaf">
                            <h3>Rust</h3>
                            <p>Orange powdery spots on the underside of leaves. Caused by Hemileia vastatrix, it
                                significantly reduces yield if severe.</p>
                        </div>
                    </div>
                </div>
            </div>

            <br>
            <br>
            <br>

        </section>
    </main>

    <script src="js/script.js"></script>

</body>

</html>