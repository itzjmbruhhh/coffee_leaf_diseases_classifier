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
    <link rel="stylesheet" href="styles/style2.css">
    <title>Leaf It Up to Me || Home</title>
</head>

<body>

    <!-- Loading Overlay -->
    <div id="loadingOverlay">
        <div class="loader"></div>
        <div class="loader_text">
            <h3>Classifying please wait...</h3>
        </div>
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
    
    <script src="scripts/index.js"></script>
    <script>
        <?php if (!empty($prediction) || !empty($error)): ?>
            window.__SHOW_MODAL__ = true;
        <?php else: ?>
            window.__SHOW_MODAL__ = false;
        <?php endif; ?>
    </script>

</body>

</html>