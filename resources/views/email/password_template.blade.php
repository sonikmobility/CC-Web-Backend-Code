<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">
    <link rel="icon" href="images/favicon.ico">

    <title>Email</title>

    <!-- Bootstrap core CSS -->
    <link href="css/bootstrap.css" rel="stylesheet">

    <!-- Custom styles for this template -->
    <style type="text/css">

body {
    padding: 100px 0;
    margin: 0;
    background: #fff;
}
@charset "utf-8";
/* CSS Document */

/* Client-specific Styles */
div, p, a, li, td {
  -webkit-text-size-adjust:none;
}
#outlook a {
  padding:0;
} /* Force Outlook to provide a "view in browser" menu link. */
html {
  width: 100%;
}
body {
  width:100% !important;
  -webkit-text-size-adjust:100%;
  -ms-text-size-adjust:100%;
  margin:0;
  padding:0;
  background: #f2f2f2 !important;
}
/* Prevent Webkit and Windows Mobile platforms from changing default font sizes, while not breaking desktop design. */
.ExternalClass {
  width:100%;
} /* Force Hotmail to display emails at full width */
.ExternalClass, .ExternalClass p, .ExternalClass span, .ExternalClass font, .ExternalClass td, .ExternalClass div {
  line-height: 100%;
} /* Force Hotmail to display normal line spacing. */
#backgroundTable {
  margin:0;
  padding:0;
  width:100% !important;
  line-height: 100% !important;
}
img {
  outline:none;
  text-decoration:none;
  border:none;
  -ms-interpolation-mode: bicubic;
}
a img {
  border:none;
}
.image_fix {
  display:block;
}
p {
  margin: 0px 0px !important;
}
table td {
  border-collapse: collapse;
}
table {
  border-collapse:collapse;
  mso-table-lspace:0pt;
  mso-table-rspace:0pt;
}
a {
  color: #33b9ff;
  text-decoration: none;
  text-decoration:none!important;
}
.mauto { margin:0 auto 0 auto !important;  }
/*STYLES*/
table[class=full] {
  width: 100%;
  clear: both;
}
/*IPAD STYLES*/
@media only screen and (max-width: 640px) {
 a[href^="tel"], a[href^="sms"] {
 text-decoration: none;
 color: #33b9ff; /* or whatever your want */
 pointer-events: none;
 cursor: default;
}
 .mobile_link a[href^="tel"], .mobile_link a[href^="sms"] {
 text-decoration: default;
 color: #33b9ff !important;
 pointer-events: auto;
 cursor: default;
}
 table[class=devicewidth] {
width: 440px!important;
text-align:center!important;
}
 table[class=devicewidthinner] {
width: 420px!important;
text-align:center!important;
}
 img[class=banner] {
  width: 440px!important;
  height:220px!important;
}
 img[class=col2img] {
  width: 440px!important;
  height:220px!important;
}
}
/*IPHONE STYLES*/
@media only screen and (max-width: 480px) {
 a[href^="tel"], a[href^="sms"] {
 text-decoration: none;
 color: #33b9ff; /* or whatever your want */
 pointer-events: none;
 cursor: default;
}
 .mobile_link a[href^="tel"], .mobile_link a[href^="sms"] {
 text-decoration: default;
 color: #33b9ff !important;
 pointer-events: auto;
 cursor: default;
}
 table[class=devicewidth] {
width: 380px!important;
text-align:center!important;
}
 table[class=devicewidthinner] {
width: 260px!important;
text-align:center!important;
}
}

@media only screen and (max-width: 375px) {
 a[href^="tel"], a[href^="sms"] {
 text-decoration: none;
 color: #33b9ff; /* or whatever your want */
 pointer-events: none;
 cursor: default;
}
 .mobile_link a[href^="tel"], .mobile_link a[href^="sms"] {
 text-decoration: default;
 color: #33b9ff !important;
 pointer-events: auto;
 cursor: default;
}
 table[class=devicewidth] {
width: 350px!important;
text-align:center!important;
}
 table[class=devicewidthinner] {
width: 260px!important;
text-align:center!important;
}
}

/*IPHONE STYLES*/
@media only screen and (max-width: 320px) {
 a[href^="tel"], a[href^="sms"] {
 text-decoration: none;
 color: #33b9ff; /* or whatever your want */
 pointer-events: none;
 cursor: default;
}
 .mobile_link a[href^="tel"], .mobile_link a[href^="sms"] {
 text-decoration: default;
 color: #33b9ff !important;
 pointer-events: auto;
 cursor: default;
}
 table[class=devicewidth] {
width: 300px!important;
text-align:center!important;
}
 table[class=devicewidthinner] {
width: 260px!important;
text-align:center!important;
}


}

    </style>
  </head>

   <body style="padding: 30px 0; margin: 0px; background: #f2f2f2;">
<!-- Start of header -->
<table width="100%" bgcolor="#f2f2f2" cellpadding="0" cellspacing="0" border="0" id="backgroundTable" st-sortable="header">
<tbody>
<tr>
  <td>
    <table width="640" bgcolor="#ffffff" cellpadding="0" cellspacing="0" border="0" align="center" class="devicewidth">
        <tbody>
          <tr>
            <td align="center" valign="middle" style="padding: 15px 0; border: solid 1px #f2f2f2; background: #000000; "><img src="<?=$data['img']?>" height="50" width="" /></td>
          </tr>
          <tr>
            <td align="center" valign="middle" style="padding: 30px; border: solid 1px #f2f2f2;">
              <div style="font-family: Exo,Arial, Helvetica, sans-serif;  font-size: 30px; color: #000000; text-align: center; margin-bottom: 20px;line-height: normal;"> <?php echo $data['header']; ?></div>
              <div style="font-family: Exo,Arial, Helvetica, sans-serif; font-size: 14px; color: #676767; text-align: left; line-height: normal; margin-top: 14px;"> <?php echo $data['content']; ?></div>
            </td>
          </tr>

        </tbody>
        </table>
  </td>
</tr>
</tbody>
</table>
<!-- End of Header -->

      </td>
  </td>
</tr>

</tbody>
</table>
<!-- End of Left Image -->



<!-- Start of Left Image -->
<table width="100%" bgcolor="#f2f2f2" cellpadding="0" cellspacing="0" border="0" id="backgroundTable" st-sortable="left-image" align="center">
<tbody>
<tr>
  <td>
    <table width="640" bgcolor="#232323" cellpadding="0" cellspacing="0" border="0" align="center" class="devicewidth">
        <tbody>
        <tr>
          <td>
            <!-- Start of left column -->
            <table width="320" align="center" border="0" cellpadding="0" cellspacing="0" class="devicewidth">
            <tbody>
            <!-- image -->
            <tr>
              <td width="320" height="" align="center" class="devicewidth" style="padding-top:20px; padding-bottom: 20px;">
                <font face="Arial, Helvetica, sans-serif" size="3" color="#ccc" >
            <span style="font-family: Exo,Arial, Helvetica, sans-serif;text-align: center;  font-size: 12px; color: #cccccc;"><?php echo $data['Footer']; ?></span></font>
              </td>
            </tr>
            <!-- /image -->
            </tbody>
            </table>
            <!-- end of left column -->

            <!-- end of right column -->
          </td>
        </tr>
        </tbody>
        </table>
  </td>
</tr>

</tbody>
</table>
<!-- End of Left Image -->
  </body>
</html>
