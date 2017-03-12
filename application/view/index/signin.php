<?=$this->getHeader()?>

    <div class="row">
        <div class="container col-md-4 col-md-offset-4">

          <form class="form-signin" action="/account/login" method="post">
            <h2 class="form-signin-heading">Sign in</h2>
            <label for="inputEmail" class="sr-only">Email address</label>
            <input type="email" id="inputEmail" class="form-control" placeholder="Email address" required autofocus>
            <label for="inputPassword" class="sr-only">Password</label>
            <input type="password" id="inputPassword" class="form-control" placeholder="Password" required>
            <div class="checkbox">
              <label>
                <input type="checkbox" value="remember-me"> Remember me
              </label>
            </div>
            <button class="btn btn-lg btn-primary btn-block" type="submit">Sign in</button>
          </form>

          <form class="form-signin" action="/account/create" method="post">
            <h2 class="form-signin-heading">Create Account</h2>
            <label for="name" class="sr-only">Account name</label>
            <input type="text" id="name" class="form-control" placeholder="Account name" required>
            <label for="email" class="sr-only">Email address</label>
            <input type="email" id="email" class="form-control" placeholder="Email address" required>
            <label for="password" class="sr-only">Password</label>
            <input type="password" id="password" class="form-control" placeholder="Password" required>
            <label for="password2" class="sr-only">Repeat password</label>
            <input type="password" id="password2" class="form-control" placeholder="Repeat Password">
            <div class="checkbox">
              <label>
                <input type="checkbox" value="remember-me"> Remember me
              </label>
            </div>
            <button class="btn btn-lg btn-primary btn-block" type="submit">Create</button>
          </form>

        </div> <!-- /container -->
    </div>

<?=$this->getFooter()?>
