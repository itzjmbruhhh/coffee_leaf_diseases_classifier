<!-- forbidden.php -->
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <link rel="icon" type="image/png" href="../components/images/icon.png">
    <link rel="stylesheet" href="styles/style.css">
    <title>Access Forbidden</title>
</head>

<body>
    <section>
        <div class="container">
            <div class="notify">
                <h1>403 Forbidden</h1>
                <p>You do not have permission to access this page.</p>
                <a href="index">Go back to home</a>
            </div>
        </div>
    </section>
</body>

<style>
    h1 {
        font-size: 3rem;
        /* large heading */
        margin-bottom: 20px;
        color: var(--color-dark-brown);
        /* red */
    }

    p {
        font-size: 1.2rem;
        margin-bottom: 30px;
    }

    a {
        font-family: var(--font-2);
        background-color: var(--color-medium-brown);
        color: var(--color-creamy-beige);
        padding: 12px 28px;
        text-decoration: none;
        font-weight: bold;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(123, 79, 36, 0.5);
        transition: background-color 0.3s ease, color 0.3s ease;
        display: inline-block;
        font-size: 1rem;
        margin-top: 30px;
    }

    a:hover {
        background-color: var(--color-dark-brown);
        color: var(--color-light-cream);
        box-shadow: 0 6px 8px rgba(75, 46, 0, 0.7);
    }

    .notify {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }
</style>

</html>