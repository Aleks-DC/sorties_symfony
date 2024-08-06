<!DOCTYPE html>
<html>
<head>
    <title>Sortir.com</title>
    <link href="../node_modules/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .form-container {
            margin-top: 100px;
            padding: 30px;
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .form-container h2 {
            margin-bottom: 30px;
        }
        .form-container .btn-primary {
            width: 100%;
        }
    </style>
</head>
<body class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                    <img src="sortir.png" class="mx-auto d-block pt-3" alt="logo du site sortir.com">
            </div>
        </div>
    <div>
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="form-container">
                    <form action="includes/login.inc.php" method="post">
                        <h2 class="text-center">Connexion</h2>
                        <div class="form-group">
                            <label for="InputEmail">Adresse mail ou pseudo</label>
                            <input type="text" class="form-control mb-4" id="InputEmail" name="mailuid" placeholder="Email / Pseudo ...">
                        </div>
                        <div class="form-group">
                            <label for="InputPassword">Mot de passe</label>
                            <input type="password" class="form-control mb-4" id="InputPassword" name="pwd" placeholder="Mot de passe ...">
                        </div>
                        <div class="checkbox-rememberme">
                            <input type="checkbox" id="rememberme" name="rememberme">
                            <label for="rememberme" class="mb-3">Se souvenir de moi</label>
                        </div>
                        <button type="submit" class="btn btn-primary" name="login-submit">Connexion</button> 
                    </form>
                    <div class="MotDePasseOublie mt-3">
                        <a href="reset-password.php">Mot de passe oublié ?</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <a href="dashboard.php" class="btn btn-link mt-3 d-block text-center">Skip [DEV]</a>
</body>
</html>