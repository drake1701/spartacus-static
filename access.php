<?php
//put sha1() encrypted password here
$password = 'edcf0a57f1fcc4b7e77971760af683abf6dd4382';

if (!isset($_SESSION['loggedIn'])) {
    $_SESSION['loggedIn'] = false;
}

if(is_array($_POST) && !empty($_POST['password'])) {
    if(sha1($_POST['password']) == $password)
        $_SESSION['loggedIn'] = true;
}

if (!$_SESSION['loggedIn']): ?>
    <div data-role="content" style="max-width:400px">
        <form enctype="application/x-www-form-urlencoded" method="post">
            <h2>Please sign in</h2>
            <dl class="zend_form">
                <dt>
                    <label for="inputPassword">Password</label>
                </dt>
                <dd>
                    <input type="password" id="password" name="password" placeholder="Password" required autofocus>
                </dd>
            </dl>
            <button type="submit" class="btn-lg">Sign in</button>
        </form>
    </div>
    <?php
    die();
endif;
?>
