<nav class="navbar">
    <img src="components/icon.png" alt="">
    <div class="nav-title">Leaf It Up to Me</div>
    <div class="nav-links">

        <a class="navBtn" href="../components/logout" onclick="return confirmLogout()">Logout</a>
    </div>
</nav>

<script>
function confirmLogout() {
    return confirm("Are you sure you want to log out?");
}
</script>