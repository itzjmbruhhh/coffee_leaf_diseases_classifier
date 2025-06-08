<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: ../login/login");
    exit();
}

if ((int) $_SESSION['account_type'] === 1) {
    header("Location: ../forbidden");
    exit();
}

include '../components/connection.php';

// Fetch users for display
$query = "SELECT * FROM users WHERE account_type != 0";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="stylesheet" href="../styles/style.css" />
    <link rel="stylesheet" href="../styles/tables.css" />
    <link rel="icon" type="image/png" href="../components/images/icon.png" />
    <title>Leaf It Up to Me || Admin</title>

    <style>
        /* Your existing button styles */
        .table-container {
            width: 80vw;
            text-align: center;
        }

        .table-container a {
            display: inline-block;
            padding: 6px 12px;
            margin: 0 4px;
            color: white !important;
            text-decoration: none;
            border-radius: 4px;
            font-weight: 600;
            font-size: 0.9rem;
            transition: background-color 0.3s ease;
            cursor: pointer;
        }

        .delete {
            background-color: rgb(255, 30, 0);
        }

        .edit {
            background-color: #007bff;
        }

        .table-container a:hover {
            background-color: #0056b3;
        }

        .table-container a:active {
            background-color: #004085;
        }

        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 10% auto;
            padding: 20px;
            border-radius: 8px;
            width: 400px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.25);
            position: relative;
        }

        .modal-content h3 {
            margin-top: 0;
        }

        .modal-content label {
            display: block;
            margin-top: 10px;
            font-weight: 600;
        }

        .modal-content input,
        .modal-content select {
            width: 100%;
            padding: 8px;
            margin-top: 4px;
            box-sizing: border-box;
        }

        .modal-content button {
            margin-top: 15px;
            padding: 8px 16px;
            background-color: #007bff;
            border: none;
            color: white;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
        }

        .modal-content button:hover {
            background-color: #0056b3;
        }

        .close-modal {
            position: absolute;
            top: 8px;
            right: 12px;
            font-size: 22px;
            font-weight: bold;
            cursor: pointer;
            color: #333;
        }
    </style>

</head>

<body>

    <header><?php include "nav_index.php"; ?></header>

    <main>
        <section>
            <div class="table-container">
                <h2>Users Management</h2>
                <center>
                    <table>
                        <thead>
                            <tr>
                                <th>User ID</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr data-userid="<?= $row['user_id'] ?>"
                                    data-username="<?= htmlspecialchars($row['username'], ENT_QUOTES) ?>"
                                    data-email="<?= htmlspecialchars($row['email'], ENT_QUOTES) ?>"
                                    data-account_type="<?= $row['account_type'] ?>">
                                    <td><?= $row['user_id'] ?></td>
                                    <td><?= htmlspecialchars($row['username']) ?></td>
                                    <td><?= htmlspecialchars($row['email']) ?></td>
                                    <td>
                                        <a class="edit" href="#" onclick="openEditModal(this)">Edit</a> |
                                        <a class="delete" href="delete_user.php?id=<?= $row['user_id'] ?>"
                                            onclick="return confirm('Are you sure you want to delete this user?');">Delete</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </center>
            </div>
        </section>
    </main>

    <!-- Edit User Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeEditModal()">&times;</span>
            <h3>Edit User</h3>
            <form id="editUserForm">
                <input type="hidden" name="user_id" id="edit_user_id" />
                <label for="edit_username">Username</label>
                <input type="text" id="edit_username" name="username" required />
                <label for="edit_email">Email</label>
                <input type="email" id="edit_email" name="email" required />
                <label for="edit_account_type">Account Type</label>
                <select id="edit_account_type" name="account_type" required>
                    <option value="0">Admin</option>
                    <option value="1">User</option>
                </select>
                <label for="edit_password">Password (leave blank to keep unchanged)</label>
                <input type="password" id="edit_password" name="password" autocomplete="new-password" />
                <button type="submit">Save Changes</button>
            </form>
            <div id="editFormMsg" style="margin-top:10px;color:red;"></div>
        </div>
    </div>

    <script>
        // Open modal and populate fields from clicked row
        function openEditModal(el) {
            const tr = el.closest('tr');
            const userId = tr.dataset.userid;
            const username = tr.dataset.username;
            const email = tr.dataset.email;
            const accountType = tr.dataset.account_type;

            document.getElementById('edit_user_id').value = userId;
            document.getElementById('edit_username').value = username;
            document.getElementById('edit_email').value = email;
            document.getElementById('edit_account_type').value = accountType;
            document.getElementById('edit_password').value = ''; // clear password field

            document.getElementById('editFormMsg').textContent = '';

            document.getElementById('editModal').style.display = 'block';
        }

        // Close modal
        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        // Submit edit form with AJAX
        document.getElementById('editUserForm').addEventListener('submit', function (e) {
            e.preventDefault();

            const formData = new FormData(this);

            fetch('update_user.php', {
                method: 'POST',
                body: formData
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        // Update row in the table without reload
                        const tr = document.querySelector(`tr[data-userid='${formData.get('user_id')}']`);
                        if (tr) {
                            tr.dataset.username = formData.get('username');
                            tr.dataset.email = formData.get('email');
                            tr.dataset.account_type = formData.get('account_type');

                            tr.children[1].textContent = formData.get('username');
                            tr.children[2].textContent = formData.get('email');
                            tr.children[3].textContent = formData.get('account_type') === '0' ? 'Admin' : 'User';
                        }

                        closeEditModal();
                        alert('User updated successfully.');
                    } else {
                        document.getElementById('editFormMsg').textContent = data.message || 'Failed to update user.';
                    }
                })
                .catch(() => {
                    document.getElementById('editFormMsg').textContent = 'An error occurred.';
                });
        });

        // Close modal on outside click
        window.onclick = function (event) {
            const modal = document.getElementById('editModal');
            if (event.target == modal) {
                closeEditModal();
            }
        }
    </script>

</body>

</html>