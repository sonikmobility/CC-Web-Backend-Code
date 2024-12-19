<!doctype html>
<html lang="en" class="no-js">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link href='https://fonts.googleapis.com/css?family=Open+Sans:400,300,600,700' rel='stylesheet' type='text/css'>
	<title>{{$page_data->page_title}} </title>
	<style>
		.btn {
			display: inline-block;
			margin-bottom: 0;
			font-weight: 400;
			text-align: center;
			white-space: nowrap;
			vertical-align: middle;
			-ms-touch-action: manipulation;
			touch-action: manipulation;
			cursor: pointer;
			background-image: none;
			border: 1px solid transparent;
			padding: 6px 12px;
			font-size: 14px;
			line-height: 1.42857143;
			border-radius: 4px;
			-webkit-user-select: none;
			-moz-user-select: none;
			-ms-user-select: none;
			user-select: none;
		}

		.cd-faq p {
			font-size: 12px;
			margin: 10px;
			margin-left: 0px;
			line-height: 18px;
		}

		.btn-primary {
			color: #fff;
			background-color: #ed1c24;
			border-color: #ed1c24;
			margin: 10px;
			width: 25%;
			padding: 10px;
			margin-left: 0px;
			padding-left: 0px;
		}

		@media screen and (max-width: 480px) {
			.btn-primary {
				color: #fff;
		    background-color: #ed1c24;
		    border-color: #ed1c24;
		    margin: 10px;
		    width: 98%;
		    padding: 10px;
		    margin-left: 0px;
		    padding-left: 0px;
			}
        }

        @media only screen and (max-width: 812px) {
            .btn-primary {
                color: #fff;
                background-color: #ed1c24;
                border-color: #ed1c24;
                margin: 10px;
                width: 98%;
                padding: 10px;
                margin-left: 0px;
                padding-left: 0px;
            }
        }
        header h1 {
        color :var(--main-button-color);
        }
    </style>
</head>
    <body>
        <header>
            <h1> {{$page_data->page_title}} </h1>
        </header>
        <section class="cd-faq">
            {!!html_entity_decode($page_data->page_content)!!}
        </section> 
    </body>
</html>
