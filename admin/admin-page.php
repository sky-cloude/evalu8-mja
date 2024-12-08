<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="admin-navbar.css">
    <link rel="stylesheet" type="text/css" href="admin-style.css">
    <?php include('../header.php'); ?>
</head>
<body>
    <?php $id=intval($_GET['id']);?>

    <div class="wrapper">
        <div class="sidebar">
            <?php include('sidebar.php'); ?>
        </div>

        <div class="right-side-contents w-100">
            <?php include('admin-navigation-bar.php'); ?>

            <div class="main-content>
               <!-- Dashboard Content -->
                <div id="dashboard-content" class="main-content container active-content">
                    <h1 class="text-center pt-4"></h1>
                </div>
            </div>
        </div>

        
    </div>

    <?php include('footer.php'); ?>
    

</body>
</html>