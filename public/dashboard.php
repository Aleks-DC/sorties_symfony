<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord</title>
    <link href="../node_modules/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <nav class="navbar navbar-light bg-light">
            <a class="navbar-brand" href="#">Mon Tableau de Bord</a>
        </nav>

        <form>
            <div class="form-row">
                <div class="col">
                    <input type="text" class="form-control" placeholder="Rechercher">
                </div>
                <div class="col">
                    <select class="form-control">
                        <option>Tous les statuts</option>
                        <option>En cours</option>
                        <option>Terminé</option>
                    </select>
                </div>
                <div class="col">
                    <input type="date" class="form-control">
                </div>
            </div>
        </form>

        <table class="table table-striped table-custom">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nom</th>
                    <th>Statut</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <!-- <?php
                while ($row = mysqli_fetch_assoc($result)) {
                    echo "<tr>";
                    echo "<td>" . $row['id'] . "</td>";
                    echo "<td>" . $row['nom'] . "</td>";
                    echo "<td><a href='#' class='btn btn-primary btn-sm'>Modifier</a> <a href='#' class='btn btn-danger btn-sm'>Supprimer</a></td>";
                    echo "</tr>";
                }
                ?> -->
            </tbody>
        </table>

        <div class="text-right">
            <button class="btn btn-primary">Créer une sortie</button>
        </div>
    </div>

    <script src="../node_modules/jquery/dist/jquery.min.js"></script>
    <script src="../node_modules/bootstrap/dist/js/bootstrap.min.js"></script>
</body>
</html>
