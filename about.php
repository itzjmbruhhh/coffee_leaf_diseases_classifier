<?php

session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login/login");
    exit();
}


?>

<!DOCTYPE html>
<html lang="en">

<head><?php include "components/head.php"; ?>
    <title>Leaf It Up to Me || About Us</title>
</head>

<body>

    <header><?php include "components/nav_about.php"; ?></header>

    <main>
        <section>

            <!-- ABOUT US -->

            <center>
                <h2 style="font-size: 40px;">About Us</h2>
            </center>

            <div class="container">
                <div class="disease-cards">
                    <!-- Creator 1 -->
                    <div class="creator-card">
                        <div style="position:relative;">
                            <img src="components/images/creator1.png" alt="Creator 1">
                            <div class="creator-img-overlay">
                                <a href="https://github.com/itzjmbruhhh" target="_blank" rel="noopener noreferrer"
                                    title="GitHub">
                                    <!-- GitHub SVG icon -->
                                    <svg width="32" height="32" fill="currentColor" viewBox="0 0 24 24"
                                        aria-hidden="true">
                                        <path
                                            d="M12 0C5.37 0 0 5.373 0 12c0 5.303 3.438 9.8 8.205 11.387.6.113.82-.258.82-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.84 1.237 1.84 1.237 1.07 1.834 2.809 1.304 3.495.997.108-.775.418-1.305.762-1.605-2.665-.305-5.466-1.334-5.466-5.931 0-1.31.469-2.381 1.236-3.221-.124-.303-.535-1.523.117-3.176 0 0 1.008-.322 3.301 1.23a11.52 11.52 0 0 1 3.003-.404c1.018.005 2.045.138 3.003.404 2.291-1.553 3.297-1.23 3.297-1.23.653 1.653.242 2.873.119 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.803 5.624-5.475 5.921.43.371.823 1.102.823 2.222v3.293c0 .322.218.694.825.576C20.565 21.796 24 17.299 24 12c0-6.627-5.373-12-12-12z" />
                                    </svg>
                                </a>
                            </div>
                        </div>
                        <h3>John Michael Reyes</h3>
                        <p>Led the AI model training and was responsible for the CNN-based plant disease detection
                            system.</p>
                    </div>

                    <!-- Creator 2 -->
                    <div class="creator-card">
                        <div style="position:relative;">
                            <img src="components/images/creator2.png" alt="Creator 1">
                            <div class="creator-img-overlay">
                                <a href="https://github.com/kylajamito" target="_blank" rel="noopener noreferrer"
                                    title="GitHub">
                                    <!-- GitHub SVG icon -->
                                    <svg width="32" height="32" fill="currentColor" viewBox="0 0 24 24"
                                        aria-hidden="true">
                                        <path
                                            d="M12 0C5.37 0 0 5.373 0 12c0 5.303 3.438 9.8 8.205 11.387.6.113.82-.258.82-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.84 1.237 1.84 1.237 1.07 1.834 2.809 1.304 3.495.997.108-.775.418-1.305.762-1.605-2.665-.305-5.466-1.334-5.466-5.931 0-1.31.469-2.381 1.236-3.221-.124-.303-.535-1.523.117-3.176 0 0 1.008-.322 3.301 1.23a11.52 11.52 0 0 1 3.003-.404c1.018.005 2.045.138 3.003.404 2.291-1.553 3.297-1.23 3.297-1.23.653 1.653.242 2.873.119 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.803 5.624-5.475 5.921.43.371.823 1.102.823 2.222v3.293c0 .322.218.694.825.576C20.565 21.796 24 17.299 24 12c0-6.627-5.373-12-12-12z" />
                                    </svg>
                                </a>
                            </div>
                        </div>
                        <h3>Kyla Jane Jamito</h3>
                        <p>Handled backend development and integrated the AI with the prediction system.</p>
                    </div>

                    <!-- Creator 3 -->
                    <div class="creator-card">
                        <div style="position:relative;">
                            <img src="components/images/creator3.png" alt="Creator 1">
                            <div class="creator-img-overlay">
                                <a href="https://github.com/patrickeva" target="_blank" rel="noopener noreferrer"
                                    title="GitHub">
                                    <!-- GitHub SVG icon -->
                                    <svg width="32" height="32" fill="currentColor" viewBox="0 0 24 24"
                                        aria-hidden="true">
                                        <path
                                            d="M12 0C5.37 0 0 5.373 0 12c0 5.303 3.438 9.8 8.205 11.387.6.113.82-.258.82-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.84 1.237 1.84 1.237 1.07 1.834 2.809 1.304 3.495.997.108-.775.418-1.305.762-1.605-2.665-.305-5.466-1.334-5.466-5.931 0-1.31.469-2.381 1.236-3.221-.124-.303-.535-1.523.117-3.176 0 0 1.008-.322 3.301 1.23a11.52 11.52 0 0 1 3.003-.404c1.018.005 2.045.138 3.003.404 2.291-1.553 3.297-1.23 3.297-1.23.653 1.653.242 2.873.119 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.803 5.624-5.475 5.921.43.371.823 1.102.823 2.222v3.293c0 .322.218.694.825.576C20.565 21.796 24 17.299 24 12c0-6.627-5.373-12-12-12z" />
                                    </svg>
                                </a>
                            </div>
                        </div>
                        <h3>Patrick Eva</h3>
                        <p>Designed the user interface, focusing on accessibility and clean UX for farmers and
                            students alike.</p>
                    </div>

                </div>
            </div>

            <center>
                <div class="container about-website">
                    <p>
                        We are <strong>third-year Bachelor of Science in Computer Science students</strong> from
                        <strong>National University – Lipa Campus</strong>.
                        This project, entitled <strong>"Leaf It Up to Me"</strong>, was developed as a course
                        requirement for our Deep Learning subject.
                        It serves as a practical application of the concepts and techniques we have learned throughout
                        the term,
                        particularly in the areas of convolutional neural networks (CNNs),
                        image classification,
                        and AI-driven decision support systems.
                        <br>
                        <br>

                        Our primary objective is to harness the power of artificial intelligence to
                        address
                        real-world problems in the agricultural sector. By creating a system that
                        accurately classifies
                        coffee leaf conditions—specifically identifying <strong>healthy
                            leaves</strong>, as well as those affected by
                        <strong>Miner</strong>, <strong>Phoma</strong>, or <strong>Rust</strong> diseases—we aim to
                        assist farmers in early detection
                        and intervention, leading to improved <strong>crop health and productivity</strong>.
                        <br>
                        <br>
                        Through this project, we aspire not only to fulfill academic requirements but
                        also to contribute
                        meaningfully to the advancement of smart farming practices in the Philippines.
                    </p>

                </div>
            </center>

            <div class="container footer">
                <?php include "components/about_footer.php"; ?>
            </div>

        </section>

    </main>



    <script src="js/script.js"></script>

</body>

</html>