<?=$this->getHeader()?>

<h2>Welcome to <?=APP_NAME?></h2>

<? if(isset($_SESSION['account'])): ?>
<div>
    Welcome <?=$_SESSION['account']->name?>
</div>
<? endif ?>

<div>
    <h3>Login</h3>
    <form action="/account/login" method="post">
        Login <input type="text" name="login">
        <br>
        Password <input type="password" name="password">
        <input type="submit">
    </form>
</div>

<div>
    <h3>Create Account</h3>
    <form action="/account/create" method="post">
        <input type="hidden" name="source_id" value="1">
        Name <input type="text" name="name">
        <br>
        Password <input type="password" name="password">
        <br>
        Email <input type="password" name="email">
        <input type="submit" value="Create">
    </form>
</div>

<?=$this->getFooter()?>
