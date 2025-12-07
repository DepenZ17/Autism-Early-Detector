<?php
session_start();
// Pastikan path ini benar. Jika koneksi.php ada di aed/db/koneksi.php
// dan index.php ada di aed/index.php, maka path relatifnya adalah 'db/koneksi.php'
include 'db/koneksi.php'; 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Autism Early Detector</title>
    <link rel="icon" href="images/aed.png" type="image/png">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/style.css"> 
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <div class="container-fluid py-3 px-4 d-flex justify-content-end align-items-center main-header">
    <a href="register.php" class="btn btn-primary me-2">Take The Test!</a>
    <a href="login.php" class="btn btn-light d-flex align-items-center icon-button">
        <span class="me-2">Sign In!</span>
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-person-circle" viewBox="0 0 16 16">
            <path d="M13.468 12.37C12.758 11.226 11.481 10.5 10 10.5s-2.758.726-3.468 1.87A6.978 6.978 0 0 1 8 15a6.978 6.978 0 0 1 5.468-2.63z"/>
            <path fill-rule="evenodd" d="M8 9a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/>
            <path fill-rule="evenodd" d="M8 1a7 7 0 1 0 0 14A7 7 0 0 0 8 1z"/>
        </svg>
    </a>
    </div>

    <div class="container-fluid hero full-height">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6 hero-content text-start">
                    <h4 class="mb-4">
                        CHECK WHETHER YOUR LOVED <br>
                        ONES NEED AUTISM RELATED CARE!<br>
                        WITH OUR AUTISM EARLY DETECTOR
                    </h4>
                    <div class="mt-4">
                        <a href="register.php" class="hero-btn">TAKE THE TEST!</a>
                    </div>
                </div>

                <div class="col-md-6 d-flex justify-content-end align-items-center">
                    <div class="image-wrapper-hero">
                        <img src="images/therapy.png" class="img-fluid rounded-image-hero" alt="Illustration">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="section-content full-height">
        <div class="container content-box">
            <h2 class="text-center mb-5">Understanding Autism</h2>

            <div class="row mb-5 align-items-center">
                <div class="col-md-6">
                    <h4>What is Autism?</h4>
                    <p>Autism Spectrum Disorder (ASD) is a developmental disorder that affects communication and behavior. Symptoms generally appear in the first two years of life and vary widely among individuals.</p>
                </div>
                <div class="col-md-6">
                    <img src="images/austim.png" class="img-fluid rounded-image" alt="Autism Info">
                </div>
            </div>

            <div class="row mb-5 align-items-center flex-md-row-reverse">
                <div class="col-md-6">
                    <h4>Why Early Detection Matters</h4>
                    <p>Early diagnosis of autism can help children and families receive the support they need. Our system helps screen potential signs of autism quickly and efficiently.</p>
                </div>
                <div class="col-md-6">
                    <img src="images/detector.png" class="img-fluid rounded-image" alt="Early Detection">
                </div>
            </div>

            <div class="row align-items-center">
                <div class="col-md-6">
                    <h4>How Our Test Works</h4>
                    <p>By answering 10 simple behavioral questions, our system uses machine learning to assess the likelihood of autism. The process is private, quick, and informative.</p>
                </div>
                <div class="col-md-6">
                    <img src="images/Aq.png" class="img-fluid rounded-image" alt="How It Works">
                </div>
            </div>
        </div>
    </div>

    <footer class="bg-dark text-white text-center py-4">
        <div class="container">
            <span>&copy; <?= date('Y') ?> - Duha Nur Pambudi (2021230031) - Universitas Darma Persada</span>
        </div>
    </footer>

    <script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>