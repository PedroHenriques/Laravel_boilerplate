<html>
  <body>
    <h4>Congratulations on registering your account with {{ config('app.name') }}</h4>
    <p>All that is left to complete the registration process is activating your account.</p>
    <p>The link below will complete the process. You can either click it or copy and paste it into 
    your browser's address bar.</p>
    <br/><br/>
    <p>Activation link: <a href="{{ $activationURL }}">{{ $activationURL }}</a></p>
  </body>
</html>