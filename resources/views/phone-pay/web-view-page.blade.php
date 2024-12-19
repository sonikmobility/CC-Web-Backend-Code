<html>
  <head>
    <title>PhonePe Payment Form</title>
    <style>
      *{
        margin: 0;
        padding: 0;
        box-sizing:border-box;
      }
      body{
        font-family:Verdana, sans-serif;
      }
      .pay-section{
        min-height:100vh;
        display:grid;
        place-items:center;
      }
      .container{
        max-width: 360px;
        margin: 0 auto;
        width: 100%;
      }
      .card-box{
        box-shadow: 0 0 15px rgba(0,0,0,.25);
        padding: 20px;
        background: #fff;
        border-radius:10px;
        margin-top: 20px;
      }
      .submit-btn{
        border:none;
        background:#ff3350;
        padding: 10px 15px;
        border-radius:50px;
        font-weight:600;
        color:#fff;
      }
      .input-val{
        font-size:14px;
        word-wrap: break-word;
      }
      .input-grp{
        display: flex;
        align-items:center;
      }
      .flex-between{
        display:flex;
        align-items:center;
        justify-content:space-between;
      }
      .card-box form > *{
          margin-top:20px;
      }
    </style>
  </head>
  <body>
    <div class="pay-section">
    <div class="container">
      <div class="card-box">
      <h3>Sonik Mobility Payment</h3>
      <form action="{{route('payment-process')}}" method="GET">
        <meta name="csrf-token" content="{{ csrf_token() }}">
          <div class="flex-between">
            <div class="input-grp">
              <label for="amount">Booking:</label>
              <p class="input-val">{{$booking_id}}</p>
              <input type="hidden" name="booking" value="{{$booking_id}}">
            </div>
            <div class="input-grp">
              <label for="amount">Amount:</label>
              <p class="input-val">{{$amount}}</p>
              <input type="hidden" name="amount" value="{{$amount}}">
            </div>
          </div>
          <div class="input-grp">
                <input type="submit" value="Pay with PhonePay" class="submit-btn">
          </div>
      </form>
      </div>
    </div>
    </div>
  </body>
</html>