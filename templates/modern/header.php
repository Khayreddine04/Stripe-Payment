<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <title><?php echo !empty($title) ? strip_tags($title) : 'Secure Payment Terminal'; ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?php echo !empty($logo) ? $logo : 'assets/images/favicon.png'; ?>">
    
    <!-- Modern CSS -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/modern-theme.css" rel="stylesheet">
    
    <!-- Custom Theme -->
    <?php if($custom_theme): ?>
    <link href="assets/css/custom.css.php" rel="stylesheet">
    <?php endif; ?>
    
    <!-- HTML5 Shim and Respond.js for IE8 support -->
    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
    <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
    
    <!-- jQuery -->
    <script src="assets/js/jquery-3.6.0.min.js"></script>
    
    <?php if($use_recaptcha == 'y'): ?>
    <!-- reCAPTCHA -->
    <script src='https://www.google.com/recaptcha/api.js' async defer></script>
    <?php endif; ?>
    
    <style>
        :root {
            --primary-color: #4f46e5;
            --primary-hover: #4338ca;
            --secondary-color: #6b7280;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
            --light-bg: #f9fafb;
            --dark-bg: #111827;
            --text-color: #1f2937;
            --text-light: #6b7280;
            --border-color: #e5e7eb;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            --radius-sm: 0.375rem;
            --radius: 0.5rem;
            --radius-lg: 0.75rem;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            color: var(--text-color);
            line-height: 1.6;
            background-color: var(--light-bg);
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        
        .top-notice {
            background-color: var(--primary-color);
            color: white;
            text-align: center;
            padding: 10px 0;
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        .top-notice a {
            color: white;
            text-decoration: underline;
            font-weight: 600;
        }
        
        .header {
            background: white;
            box-shadow: var(--shadow-sm);
            padding: 1rem 0;
            position: relative;
            z-index: 10;
        }
        
        .header-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1.5rem;
        }
        
        .logo-container {
            display: flex;
            align-items: center;
            gap: 1rem;
            text-decoration: none;
            color: inherit;
        }
        
        .logo {
            height: 40px;
            width: auto;
            max-width: 180px;
            object-fit: contain;
        }
        
        .site-title {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-color);
            line-height: 1.2;
        }
        
        .site-tagline {
            margin: 0.25rem 0 0;
            font-size: 0.875rem;
            color: var(--text-light);
            font-weight: 400;
        }
        
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                text-align: center;
                gap: 1rem;
            }
            
            .logo-container {
                flex-direction: column;
                text-align: center;
            }
            
            .site-title {
                font-size: 1.25rem;
            }
        }
    </style>
</head>
<body>
    <?php if(!empty($notice)): ?>
    <div class="top-notice">
        <div class="container">
            <?php echo $notice; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <header class="header">
        <div class="container">
            <div class="header-content">
                <?php if(!empty($logo)): ?>
                <a href="<?php echo $site_url; ?>" class="logo-container">
                    <img src="<?php echo $logo; ?>" alt="<?php echo !empty($title) ? strip_tags($title) : 'Logo'; ?>" class="logo">
                </a>
                <?php endif; ?>
                
                <div class="text-center text-md-<?php echo !empty($logo) ? 'end' : 'center'; ?> w-100">
                    <h1 class="site-title"><?php echo !empty($title) ? $title : 'Secure Payment'; ?></h1>
                    <?php if(!empty($description)): ?>
                    <p class="site-tagline"><?php echo $description; ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>
    
    <main class="main-content">
