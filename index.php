<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login/login");
    exit();
}

include "components/connection.php";

$username = $_SESSION['username'];
$stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $user_id = $row['user_id'];
} else {
    die("User not found.");
}

$prediction = "";
$confidence = "";
$probabilities = "";
$heatmapPath = "";
$error = "";
$uploadedImagePath = "";
$diseaseDescriptions = [
    'Healthy' => 'No action needed. Continue current care practices.',
    'Miner' => 'Remove affected leaves and apply appropriate insecticides.',
    'Phoma' => 'Prune infected areas and use fungicides as needed.',
    'Rust' => 'Apply fungicides and avoid overhead watering to reduce spread.',
];

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_FILES["imageInput"])) {
    $uploadDir = "uploads/";
    $originalName = basename($_FILES["imageInput"]["name"]);
    $uploadPath = $uploadDir . uniqid() . "_" . $originalName;
    $imagePath = $_FILES["imageInput"]["tmp_name"];
    $imageType = mime_content_type($imagePath);

    if (strpos($imageType, 'image/') !== 0) {
        $error = "Uploaded file is not an image.";
    } else {
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir);
        }

        if (move_uploaded_file($imagePath, $uploadPath)) {
            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => "http://127.0.0.1:8000/predict",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => array("file" => new CURLFile($uploadPath, $imageType, $originalName))
            ));

            $response = curl_exec($curl);
            $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);

            if ($http_code === 200) {
                $data = json_decode($response, true);
                $prediction = $data["prediction"];
                $confidence = $data["confidence"];
                $probabilities = $data["probabilities"];
                $heatmapPath = $data["heatmap_path"];
                $uploadedImagePath = $uploadPath;

                $mysqli = new mysqli("localhost", "root", "", "coffee_leaf");
                if (!$mysqli->connect_error) {
                    $stmt = $mysqli->prepare("INSERT INTO predictions (prediction, confidence, probabilities, image_path, user_id) VALUES (?, ?, ?, ?, ?)");
                    $stmt->bind_param("sdssi", $prediction, $confidence, $probabilities, $uploadPath, $user_id);
                    $stmt->execute();
                    $stmt->close();
                    $mysqli->close();
                }
            } else if ($http_code === 400 || $http_code === 422) {
                $error = json_decode($response, true)['detail'] ?? "Failed to get prediction.";
            }
        } else {
            $error = "Failed to move uploaded image.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php include "components/head.php"; ?>
    <title>Leaf It Up to Me || Home</title>
    <style>
        /* LOADING OVERLAY */
        #loadingOverlay {
            display: flex;
            flex-direction: column;
            position: fixed;
            z-index: 2000;
            top: 0;
            left: 0;
            height: 100vh;
            width: 100vw;
            background: rgba(255, 255, 255, 0.8);
            justify-content: center;
            align-items: center;
        }

        #loadingOverlay .loader {
            border: 6px solid #f3f3f3;
            border-top: 6px solid #3498db;
            border-radius: 50%;
            width: 60px;
            height: 60px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.6);
        }

        .modal-content {
            display: flex;
            flex-direction: column;
            background-color: #fefefe;
            margin: 10% auto;
            padding: 20px;
            border-radius: 8px;
            width: 90%;
            max-width: 700px;
            position: relative;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }

        #modalContent {
            display: flex;
            flex-direction: row;
            gap: 20px;
            align-items: flex-start;
            justify-content: space-between;
        }

        .prediction_image {
            flex: 1;
            max-width: 30%;
            flex-direction: row;
        }

        .prediction_results {
            flex: 2;
            border-radius: 10px;
            border: 3px dashed var(--color-dark-brown);
            padding: 10px;
            display: flex;
            flex-direction: row;
            align-items: center;
            justify-content: center;
        }

        .pred1 {
            font-size: 35px;
            margin-bottom: 0;
            margin-top: 0;
        }

        .pred2 {
            font-size: 20px;
            margin: 0;
        }

        .pred3 {
            margin-bottom: 0;
        }

        .pred3,
        .pred4 {
            text-align: center;
        }

        .prediction_desc {
            flex: 1;
            padding-right: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .modal img:first-child {
            margin-top: 5px;
        }

        .modal img {
            max-width: 100%;
            height: 120px;
            width: auto;
            border-radius: 4px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 5px;
        }

        .modal-close {
            color: #888;
            position: static;
            right: 15px;
            top: 10px;
            font-size: 30px;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.3s eas;
        }

        .modal-close:hover {
            color: #e74c3c;
        }

        .modal-close:focus {
            outline: none;
            color: #c0392b;
        }
    </style>
</head>

<body>

    <!-- Loading Overlay -->
    <div id="loadingOverlay">
        <div class="loader"></div>
        <div class="loader_text"><h3>Classifying please wait...</h3></div>
    </div>

    <header><?php include "components/nav_index.php"; ?></header>

    <main>
        <section class="index">
            <!-- IMAGE FORMS -->
            <div class="container">
                <form method="POST" enctype="multipart/form-data" class="input-form" id="leafForm">
                    <div class="image_input">
                        <div class="button-group">
                            <label for="imageInput" class="custom-file-upload">Choose Leaf Image</label>
                        </div>
                        <input type="file" id="imageInput" name="imageInput" accept="image/*" required />

                        <!-- Preview Image before submit -->
                        <img id="preview" src="#" alt="Image Preview"
                            style="display: none; max-width: 100%; margin-top: 10px;" />

                        <!-- Display File Name -->
                        <div id="fileName" style="margin-top: 10px;"></div>

                        <!-- Clear Image Button -->
                        <button id="clearImageBtn" style="display: none; margin-top: 10px;">Clear Image</button>
                    </div>

                    <button type="submit">Classify Leaf</button>
                </form>
            </div>

            <div class="container">
                <center>
                    <p style="font-size: 1.3rem;"><b>Note: Use clear coffee leaf images with less background noise for
                            better results.</b></p>
                    <p style="font-size: 0.8rem;"><em>Disclaimer: If you suspect serious issues, please seek
                            professional help for accurate
                            diagnosis and treatment.</em></p>
                </center>
            </div>

            <!-- Modal for showing results -->
            <div id="resultModal" class="modal">
                <div class="modal-content">
                    <span class="modal-close" id="modalClose" role="button" tabindex="0"
                        aria-label="Close Modal">&times;</span>
                    <br>
                    <div id="modalContent">
                        <?php if (!empty($error)): ?>
                            <p style="color: red;"><?= htmlspecialchars($error) ?></p>
                        <?php elseif (!empty($prediction)): ?>
                            <div class="prediction_results">
                                <div class="prediction_desc">
                                    <p class="pred1"><strong>Prediction:</strong> <?= htmlspecialchars($prediction) ?></p>
                                    <p class="pred2"><strong>Confidence:</strong> <?= htmlspecialchars($confidence) ?>%</p>
                                    <p class="pred3"><strong>Probabilities:</strong> <?= htmlspecialchars($probabilities) ?>
                                    </p>
                                    <?php if (isset($diseaseDescriptions[$prediction])): ?>
                                        <p class="pred4"><strong>Recommendation:</strong>
                                            <?= htmlspecialchars($diseaseDescriptions[$prediction]) ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                                <?php if ($uploadedImagePath): ?>
                                    <div class="prediction_image">
                                        <label for="">Predicted Picture</label>
                                        <img src="<?= htmlspecialchars($uploadedImagePath) ?>" alt="Uploaded Leaf Image" />
                                        <label for="">Heatmap</label>
                                        <img src="<?= htmlspecialchars($heatmapPath) ?>" alt="Heatmap Image" />
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </section>
    </main>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const imageInput = document.getElementById("imageInput");
            const preview = document.getElementById("preview");
            const fileNameDiv = document.getElementById("fileName");
            const clearImageBtn = document.getElementById("clearImageBtn");
            const modal = document.getElementById("resultModal");
            const modalClose = document.getElementById("modalClose");
            const form = document.getElementById("leafForm");
            const loadingOverlay = document.getElementById("loadingOverlay");

            // Ensure overlay is hidden on load
            loadingOverlay.style.display = "none";

            form.addEventListener("submit", function () {
                loadingOverlay.style.display = "flex";
            });

            function handleFile(file) {
                if (!file.type.startsWith("image/")) {
                    alert("Please upload a valid image file.");
                    return;
                }

                const reader = new FileReader();
                reader.onload = function (e) {
                    preview.src = e.target.result;
                    preview.style.display = "block";
                    fileNameDiv.textContent = file.name;
                    clearImageBtn.style.display = "inline-block";
                };
                reader.readAsDataURL(file);
            }

            imageInput.addEventListener("change", function () {
                const file = this.files[0];
                if (file) {
                    handleFile(file);
                }
            });

            clearImageBtn.addEventListener("click", function (e) {
                e.preventDefault();
                imageInput.value = "";
                preview.src = "#";
                preview.style.display = "none";
                fileNameDiv.textContent = "";
                clearImageBtn.style.display = "none";
            });

            modalClose.addEventListener("click", function () {
                modal.style.display = "none";
            });

            window.addEventListener("click", function (e) {
                if (e.target === modal) {
                    modal.style.display = "none";
                }
            });

            // Auto-show modal if prediction or error exists
            <?php if (!empty($prediction) || !empty($error)): ?>
                modal.style.display = "block";
            <?php endif; ?>
        });
    </script>
</body>

</html>