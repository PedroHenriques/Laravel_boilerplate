<div class="alert alert-danger" role="alert">
  This account is inactive.<br/>
  If you just registered, an activation link was sent to the provided email address.<br>
  To send a new activation link please <a href="{{ route('resendActivation', ['e' => session('email')]) }}" target="_self">click here</a>.
</div>