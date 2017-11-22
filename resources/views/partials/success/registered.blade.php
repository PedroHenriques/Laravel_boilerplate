<div class="alert alert-success" role="alert">
  <strong>Congratulations!</strong> Your account has been created.<br/>
  To complete the registration process an activation link was sent to the account's email address.<br>
  You can request a new link by <a href="{{ route('resendActivation', ['e' => session('email')]) }}" target="_self">clicking here</a>.
</div>