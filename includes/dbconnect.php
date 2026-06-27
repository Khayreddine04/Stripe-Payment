<?php

                        //EDIT ONLY FOLLOWING 5 LINES
                        define("DB_HOST", getenv('DB_HOST') ?: 'cosmopredict-new-jq5zfp'); //hostname
                        define("DB_USER", getenv('DB_USERNAME') ?: (getenv('DB_USER') ?: 'new-db')); // username
                        define("DB_PASS", getenv('DB_PASSWORD') ?: (getenv('DB_PASS') ?: 'QgTQ6QbmpxM0e')); // password
                        define("DB_NAME", getenv('DB_DATABASE') ?: (getenv('DB_NAME') ?: 'new-stripe')); //database name
                        define("DB_PORT", (int) (getenv('DB_PORT') ?: 3306));
                        define("DB_CHARSET", 'utf8mb4');
                        define("DB_PREFIX", getenv('DB_PREFIX') ?: 'vcp_pt_');

                        $db_pr=DB_PREFIX;

                        global $mysqli,$db_pr;
                        if (DB_HOST != "" && DB_USER != "" && DB_PASS != "" && DB_NAME != "") {
                            try {
                                @$mysqli = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
                            } catch (Throwable $e) {
                                $mysqli = false;
                                error_log('DB connect exception: ' . $e->getMessage());
                            }

                            if ($mysqli) {
                                mysqli_query($mysqli, 'SET NAMES ' . DB_CHARSET);
                            } else {
                                $connectError = mysqli_connect_error();
                                if (!empty($connectError)) {
                                    error_log('DB connect failed [' . DB_HOST . ':' . DB_PORT . ']: ' . $connectError);
                                }
                                die("<html><head><link rel='stylesheet' type='text/css' href='assets/bootstrap/css/bootstrap.css'></head><body><div class='row col-lg-6 col-lg-push-3'><p class=' alert-danger alert'><i class='glyphicon glyphicon-remove-circle'></i> Can't connect to database.</p></div><div class='clearfix'></div><div class='row col-lg-6 col-lg-push-3 text-center'><h3>Is this new installation?</h3></div><div class='clearfix'></div><div class='row col-lg-6 col-lg-push-3'><p class=' alert alert-warning'>Please navigate to <a href='install.php'>install.php</a> to install this application. <br/></p></div></body></html>");
                            }
                        } else { 
                            die("<html><head><link rel='stylesheet' type='text/css' href='assets/bootstrap/css/bootstrap.css'></head><body><div class='row col-lg-6 col-lg-push-3'><p class=' alert-danger alert'><i class='glyphicon glyphicon-remove-circle'></i> Can't connect to database.</p></div><div class='clearfix'></div><div class='row col-lg-6 col-lg-push-3 text-center'><h3>Is this new installation?</h3></div><div class='clearfix'></div><div class='row col-lg-6 col-lg-push-3'><p class=' alert alert-warning'>Please navigate to <a href='install.php'>install.php</a> to install this application. <br/></p></div></body></html>"); 
                        }

                        define("SALT","e19ac27c5533b5bd0d4b8fa8a8518039");