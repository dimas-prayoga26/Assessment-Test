<!DOCTYPE html>
<html lang="en">

<head>

    <!-- Title -->
    <title>Gymove  - Fitness Bootstrap Admin Dashboard Template</title>

    <!-- Meta -->
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="author" content="DexignZone">
    <meta name="robots" content="">

    <meta name="keywords" content="admin, admin dashboard, admin template, bootstrap, bootstrap 5, bootstrap 5 admin template, fitness, fitness admin, modern, responsive admin dashboard, sales dashboard, sass, ui kit, web app">
    <meta name="description" content="Discover Gymove, the ultimate fitness solution that is designed to help you achieve a healthier lifestyle with its cutting-edge features and personalized programs. Gymove is a fully mobile-responsive admin dashboard template that provides the perfect blend of exercise, nutrition, and motivation. Begin your fitness journey today with Gymove and visit DexignZone for more information.">

    <meta property="og:title" content="Gymove  - Fitness Bootstrap Admin Dashboard Template">
    <meta property="og:description" content="Discover Gymove, the ultimate fitness solution that is designed to help you achieve a healthier lifestyle with its cutting-edge features and personalized programs. Gymove is a fully mobile-responsive admin dashboard template that provides the perfect blend of exercise, nutrition, and motivation. Begin your fitness journey today with Gymove and visit DexignZone for more information.">
    <meta property="og:image" content="https://gymove.dexignzone.com/xhtml/social-image.avif">
    <meta name="format-detection" content="telephone=no">

    <!-- Mobile Specific -->
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Favicon icon -->
    <link rel="shortcut icon" type="image/x-icon" href="/gymove/assets/images/favicon.avif">

    <!-- Start - Basic CSS -->
    <link href="/gymove/assets/vendor/metismenu/dist/metisMenu.min.css" rel="stylesheet">
    <link href="/gymove/assets/vendor/bootstrap-select/dist/css/bootstrap-select.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/gymove/assets/vendor/chartist/css/chartist.min.css">
    <!-- End - Basic CSS -->

    <!-- Start - Switcher CSS -->
    <link class="main-switcher" href="/gymove/assets/css/switcher.css" rel="stylesheet">
    <!-- End - Switcher CSS -->

    <!-- Start - Style Css -->
    <link class="main-plugins" href="/gymove/assets/css/plugins.css" rel="stylesheet">
    <link class="main-css" href="/gymove/assets/css/style.css" rel="stylesheet">
    <!-- End - Style Css -->

    <style>
        :root {
            --bs-primary: #2444c9;
            --bs-heading-color: #17213b;
        }

        * {
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            margin: 0;
            background: #f7f7f8;
            color: #17213b;
            font-family: Poppins, Arial, Helvetica, sans-serif;
        }

        .clearfix::after {
            clear: both;
            content: "";
            display: block;
        }

        .container {
            width: 100%;
            max-width: 1320px;
            margin-right: auto;
            margin-left: auto;
            padding-right: 12px;
            padding-left: 12px;
        }

        .row {
            display: flex;
            flex-wrap: wrap;
            margin-right: -12px;
            margin-left: -12px;
        }

        .justify-content-center {
            justify-content: center;
        }

        .align-items-center {
            align-items: center;
        }

        .h-100 {
            height: 100%;
        }

        .col-xl-6 {
            width: 100%;
            max-width: 50%;
            padding-right: 12px;
            padding-left: 12px;
        }

        .text-center {
            text-align: center;
        }

        .error-page {
            min-height: 100vh;
            position: relative;
            background-blend-mode: luminosity;
            background-size: cover;
        }

        .error-inner {
            z-index: 1;
            position: absolute;
            left: 50%;
            top: 50%;
            width: 100%;
            max-width: 600px;
            padding: 20px;
            transform: translate(-50%, -50%);
        }

        .dz-error {
            position: relative;
            color: var(--bs-heading-color);
            font-size: 200px;
            font-weight: 900;
            line-height: 200px;
            letter-spacing: 0;
            margin: auto;
            animation: dzError 1s infinite linear alternate-reverse;
        }

        .dz-error::before,
        .dz-error::after {
            content: attr(data-text);
            position: absolute;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            pointer-events: none;
        }

        .dz-error::before {
            left: 2px;
            color: inherit;
            text-shadow: -2px 0 #f7cf47;
            clip-path: inset(36% 0 52% 0);
        }

        .dz-error::after {
            left: -2px;
            color: inherit;
            text-shadow: -2px 0 #f7cf47, 2px 2px #f7cf47;
            clip-path: inset(52% 0 36% 0);
        }

        .error-head {
            margin: 0 0 5px;
            color: #081833;
            font-size: 36px;
            font-weight: 600;
            line-height: 1.25;
        }

        .error-head i {
            color: #ff4b93;
            font-style: normal;
            font-size: 85%;
        }

        .error-head i::before {
            content: "👎";
        }

        .error-page p {
            max-width: 480px;
            margin: 0 auto 30px;
            color: #7d7f88;
            font-size: 18px;
            line-height: 1.45;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 45px;
            padding: 10px 22px;
            border-radius: 8px;
            border: 1px solid transparent;
            font-weight: 600;
            text-decoration: none;
            line-height: 1.4;
        }

        .btn-primary {
            background: var(--bs-primary);
            color: #fff;
        }

        .fs-16 {
            font-size: 16px;
        }

        @keyframes dzError {
            0% {
                transform: skew(-2deg);
            }

            100% {
                transform: skew(2deg);
            }
        }

        @media (max-width: 1199.98px) {
            .col-xl-6 {
                max-width: 100%;
            }
        }

        @media (max-width: 991.98px) {
            .dz-error {
                font-size: 150px;
                line-height: 150px;
            }

            .error-head {
                font-size: 30px;
            }

            .error-page p {
                font-size: 16px;
            }
        }

        @media (max-width: 575.98px) {
            .dz-error {
                margin-bottom: 10px;
                font-size: 80px;
                line-height: 80px;
                letter-spacing: 5px;
            }

            .error-head {
                font-size: 24px;
            }

            .error-page p {
                margin-bottom: 20px;
            }
        }
    </style>

</head>
<body>

    <!-- Start - Error Section -->
    <div class="clearfix">
        <div class="container">
            <div class="row justify-content-center h-100 align-items-center">
                <div class="col-xl-6 error-page">
                    <div class="error-inner text-center">
                        <div class="dz-error" data-text="404">404</div>
                        <h2 class="error-head"><i class="fa fa-thumbs-down text-danger"></i> The page you were looking for is not found!</h2>
                        <p>You may have mistyped the address or the page may have moved.</p>
                        <div>
                            <a class="btn btn-primary fs-16" href="/">Back to Home</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End - Error Section -->

    <!-- Start - Page Scripts -->

    <!-- Start - Script -->
    <script src="/gymove/assets/vendor/jquery/dist/jquery.min.js"></script>
    <script src="/gymove/assets/vendor/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/gymove/assets/vendor/bootstrap-select/dist/js/bootstrap-select.min.js"></script>
    <script src="/gymove/assets/vendor/@yaireo/tagify/dist/tagify.js"></script>
    <script src="/gymove/assets/vendor/metismenu/dist/metisMenu.min.js"></script>
    <script src="/gymove/assets/vendor/chart-js/chart.bundle.min.js"></script>

    <!-- Script For Custom JS -->
    <script src="/gymove/assets/js/deznav-init.js"></script>
    <script src="/gymove/assets/js/custom.js"></script>

    <!-- Script For Multiple Languages -->
    <script src="/gymove/assets/vendor/i18n/i18n.js"></script>
    <script src="/gymove/assets/js/translator.js"></script>

</body>
</html>
