<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="">
    <title>Sonik Mobility</title>

    <link href="{{ asset('resetpassword/foundation.css') }}" rel="stylesheet">
    <script src="{{ asset('resetpassword/jquery.min.js') }}"></script>
    <script src="{{ asset('resetpassword/formvalidation-custom.js') }}"></script>
    <style>
        .error {
            border: red 1px solid !important;
            margin: 0 0 1rem !important;
        }
    </style>
</head>

<body>
    <div class="container">

        @php $display='TRUE' @endphp
        <h3 style="margin: auto;color: #FF5500;text-align: center;padding: 10px"> Sonik Mobility</h3>

        @if($status=='FALSE')
        <fieldset style="max-width: 450px;margin:25px auto;">
            <div data-alert class="alert-box alert radius">
                Your link is expired or invalid.
            </div>
        </fieldset>


        @elseif($status=='Expired')
        <fieldset style="max-width: 450px;margin:25px auto;">
            <div data-alert class="alert-box alert radius">
                Your link is expired.
            </div>
        </fieldset>
        @else
        <fieldset style="max-width: 450px;margin:25px auto;">
            @if($status=='Done')
            <div class="alert-box success radius">
                {{'Password change successfully '}}<a href="#" class="close">&times;</a>
                @php $display='FALSE' @endphp
            </div>
            @endif

            @if($display=='TRUE')
            <form method="post" name="frmChangePassword" id="frmChangePassword" action="" class="form-signin login-form">
                <div class="login-logo text-center">
                    <!-- <img src="" alt="" width="100" height="100"> -->
                </div><br>

                <div class="forgot-success alert alert-success display-hide" style="display: none;">
                    <button class="close" data-close="alert"></button>
                    <span>Password reset link has been sent to email address. Please check your email.</span>
                </div>
                <div class="alert alert-success display-hide" style="display: none;">
                    <button class="close" data-close="alert"></button>
                    <span>New password has been successfully sent to your email id.</span>
                </div>
                <div class="login-wrap">
                    <div class="form-group">
                    </div>

                    <div class="form-group">
                        <div class="input-icon">
                            <i class="fa fa-user"></i>
                            <input class="form-control placeholder-no-fix email" type="text" value="{{ $email }}" autocomplete="off" placeholder="Email" name="username" readonly="" />
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="input-icon">
                            <i class="fa fa-lock"></i>
                            <input type="password" class="form-control placeholder-no-fix req password_im" autocomplete="off" placeholder="Password" name="password" id="password" />
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="input-icon">
                            <i class="fa fa-lock"></i>
                            <input type="password" class="form-control placeholder-no-fix req cpassword_im" autocomplete="off" placeholder="Confirm Password" name="cpassword" id="cpassword" />
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12 text-center"><button type="button" onclick="checkformvalidation('frmChangePassword')" class="button radius large" style="background-color: #FF5500;width: auto">SUBMIT</button></div>
                    </div>
                </div>
            </form>
            @endif
        </fieldset>
        @endif
    </div>
    <script>
        function checkformvalidation() {
            var fs = true;
            fs = Mediworks_validate();
            if (fs == false) {
                return false;
            } else {}
            $("#frmChangePassword").submit();
        }
    </script>
</body>

</html>