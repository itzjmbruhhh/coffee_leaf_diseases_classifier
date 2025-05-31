<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login/login");
    exit();
}

include "components/connection.php";

// Fetch user_id by username from session
$username = $_SESSION['username'];

$stmtUser = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
$stmtUser->bind_param("s", $username);
$stmtUser->execute();
$resultUser = $stmtUser->get_result();

if ($resultUser->num_rows === 0) {
    // User not found, redirect or handle error
    echo "User not found.";
    exit();
}

$user = $resultUser->fetch_assoc();
$user_id = (int) $user['user_id'];

// Pagination setup
$rowsPerPage = 5;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;
if ($page < 1)
    $page = 1;

// Handle deletion request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delete_id = (int) $_POST['delete_id'];

    // Delete the record securely and only if it belongs to the logged-in user
    $stmtDelete = $conn->prepare("DELETE FROM predictions WHERE id = ? AND user_id = ?");
    $stmtDelete->bind_param("ii", $delete_id, $user_id);
    if ($stmtDelete->execute()) {
        header("Location: " . $_SERVER['PHP_SELF'] . "?page=" . $page);
        exit();
    } else {
        echo "Error deleting record: " . $conn->error;
    }
}

// Get total rows count for pagination, filtered by user_id
$stmtCount = $conn->prepare("SELECT COUNT(*) AS total FROM predictions WHERE user_id = ?");
$stmtCount->bind_param("i", $user_id);
$stmtCount->execute();
$resultCount = $stmtCount->get_result();
$totalRows = $resultCount->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $rowsPerPage);

// Calculate offset for SQL LIMIT
$offset = ($page - 1) * $rowsPerPage;

// Query to get limited prediction records ordered by newest first, filtered by user_id
$stmt = $conn->prepare("SELECT id, created_at, image_path, prediction, confidence FROM predictions WHERE user_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?");
$stmt->bind_param("iii", $user_id, $rowsPerPage, $offset);
$stmt->execute();
$result = $stmt->get_result();
?>

<!-- The rest of your HTML stays the same -->


<!DOCTYPE html>
<html lang="en">

<head><?php include "components/head.php"; ?>
    <title>Leaf It Up to Me || Results History</title>
    <link rel="stylesheet" href="styles/tables.css">
</head>

<body>

    <header><?php include "components/nav_results.php"; ?></header>

    <main>
        <section>

            <!-- RESULTS TABLE -->

            <div class="container tables">
                <center>
                    <h2 style="font-size: 40px;">Results History</h2>
                </center>

                <table id="resultsTable">
                    <thead>
                        <tr>
                            <th>Prompt Date</th>
                            <th>Prompt Image</th>
                            <th>Classification</th>
                            <th>Confidence</th>
                            <th>Action</th> <!-- New column for delete button -->
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($result && $result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                $id = (int) $row['id'];
                                $date = date("Y-m-d H:i:s", strtotime($row['created_at']));
                                $prediction = htmlspecialchars($row['prediction']);
                                $confidence = htmlspecialchars($row['confidence']);
                                $imagePath = htmlspecialchars($row['image_path']);
                                ?>
                                <tr>
                                    <td><?= $date ?></td>
                                    <td>
                                        <?php if (!empty($imagePath) && file_exists($imagePath)): ?>
                                            <img src="<?= $imagePath ?>" alt="Prompt Image" style="max-width: 100px; height: auto;">
                                        <?php else: ?>
                                            <span>No image</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $prediction ?></td>
                                    <td><?= $confidence ?>%</td>
                                    <td>
                                        <form method="POST" action="" onsubmit="return confirm('Are you sure you want to delete this result?');">
                                            <input type="hidden" name="delete_id" value="<?= $id ?>">
                                            <button type="submit" style="background-color: red; color: white; border: none; padding: 5px 10px; cursor: pointer;">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php
                            }
                        } else {
                            echo "<tr><td colspan='5' style='text-align:center;'>No results found.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>

                <!-- Pagination Links -->
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?>">&laquo; Prev</a>
                    <?php else: ?>
                        <span>&laquo; Prev</span>
                    <?php endif; ?>

                    <?php
                    for ($i = 1; $i <= $totalPages; $i++) {
                        if ($i == $page) {
                            echo "<span class='current-page'>$i</span>";
                        } else {
                            echo "<a href='?page=$i'>$i</a>";
                        }
                    }
                    ?>

                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?= $page + 1 ?>">Next &raquo;</a>
                    <?php else: ?>
                        <span>Next &raquo;</span>
                    <?php endif; ?>
                </div>

            </div>

        </section>
    </main>

    <script src="js/script.js"></script>

</body>

</html>

<style>
    .pagination {
        margin: 20px 0;
        text-align: center;
    }

    .pagination a,
    .pagination span {
        display: inline-block;
        padding: 8px 16px;
        margin: 0 3px;
        border: 1px solid #ddd;
        color: var(--color-medium-brown);
        text-decoration: none;
        cursor: pointer;
    }

    .pagination a:hover {
        background-color: var(--color-medium-brown);
        color: var(--color-light-brown);
    }

    .pagination .current-page {
        background-color: var(--color-dark-brown);
        color: white;
        border-color: var(--color-medium-brown);
        cursor: default;
    }
</style>

<?php
$conn->close();
?>
