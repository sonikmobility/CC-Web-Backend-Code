//this is for make left side and center part make same height just call it after page load
function scrolldiv(id){
           $("html, body").animate({ scrollTop: 0 }, "slow");
	   //$('html,body').animate({ scrollTop: $('#'+id).offset().top }, 'slow');
	}
function make_same_height(){
        var v1 = $("#sidebar").height();
	var v2 = $("#main-content").height();
	   //     alert(v1+'mamm'+v2);

	var fv;
	if(v2 < v1)
	{ fv=v1+145;
             $("#main-content").css("height",fv); 
	}
	else
	{ fv=v2;
          $("#sidebar").css('height',fv); 
	}
    }     
    
 function make_same_height_afterloaddate(){
        var v1 = $("#PlaceUsersDataHere12").height();
	var v2 = $("#main-content").height();
 
	var fv;
	if(v2 < v1)
	{ fv=v1+145;
             $("#main-content").css("height",fv); 
	}
	else
	{ fv=v2;
          $("#PlaceUsersDataHere12").css('height',fv); 
	}
    }      
    
    
    
    
    
 $(".req").change('keypress', function(e){
					var txtval =   $(this).val();
					var getid = this.id; 
					if(txtval!="")
					{	//alert('hihi');
						$("#"+getid).removeClass('error');
						$("#"+getid).addClass('inputclass');
					}
		});
                
$(".email").change('keypress', function(e){
					var txtval =   $(this).val();
					var getid = this.id; 
					if(txtval!="")
					{
						$("#"+getid).removeClass('error');
						$("#"+getid).addClass('inputclass');
						
					}
		}); 

                
                 function Mediworks_validate()
		{  
			var flg=0;
			$(".req").each(function() { // alert('dd');
			var txtval =  $.trim($(this).val());
                        
                        if(txtval==''){
                            $(this).val('');
                        }
			 var getid = this.id; 
			
					if(txtval=="")
					{
						 $("#"+getid).removeClass('inputclass');
						  $("#"+getid).addClass('error');
						  var msg = $("#"+getid).attr("error_msg");
						  $("#"+getid).attr("placeholder",msg);
						  flg=1;
					}
					else
					{
						 $("#"+getid).removeClass('error');
						  $("#"+getid).addClass('inputclass');
				    }
		     });// end of required
			 
			 $(".req1").each(function() {  
			var txtval =   $(this).val();
			 var getid = this.id; 
			//alert(getid);
					if(txtval=="")
					{
						  $("#"+getid).removeClass('form-field1');
						  $("#"+getid).addClass('form-field1_error');
						  var msg = $("#"+getid).attr("error_msg");
						  $("#"+getid).attr("placeholder",msg);
						  flg=1;
						  
						  if(this.id=="gender")
						  {
							  $("#"+getid).removeClass('gender_style');
							  $("#"+getid).addClass('gender_style_error');
							  flg=1;
						  }
					}
					else
					{
						  $("#"+getid).removeClass('form-field1_error');
						  $("#"+getid).addClass('form-field1');
						  if(this.id=="gender")
						  {
							  $("#"+getid).removeClass('gender_style_error');
							  $("#"+getid).addClass('gender_style');
						  }
				    } //alert(flg);	
		     });// end of required
			 
			 $(".email").each(function() {
			 var txtval =  $.trim($(this).val());
			 var getid = this.id; 
			
					if(txtval=="")
					{
						 
						  $("#"+getid).addClass('error');
						  var msg = $("#"+getid).attr("error_msg");
						  if(msg=="")
							  {         
                                                               
                                                                    $("#"+getid).attr("placeholder","Please Enter Valid E-mail");
                                                                
							  }
							  else
							  {
								     $("#"+getid).attr("placeholder",msg);
							  }
						  flg=1;
					}
					else
					{    
						 var emailvalid =   validateEmail(txtval);
						 if(emailvalid==false)
						 {          $("#"+getid).val('');
						 	  $("#"+getid).removeClass('inputclass');	
 							  $("#"+getid).addClass('error');
							  $("#"+getid).attr("placeholder","Please Enter Valid E-mail");
                                                          flg=1;
						 }
						 else
						 {
							  $("#"+getid).removeClass('error');
							    $("#"+getid).addClass('inputclass');
						 }
				    }
		     });// end of required
			 

			var pass =$.trim($(".password_im").val());	 
		        var cpass =$.trim($(".cpassword_im").val());	 
			
			 if(( pass.length > 0 &&  pass.length < 6 ))
			 {
				 			$('.password_im').val('');
							$('.password_im').removeClass('inputclass');	
							$('.password_im').addClass('error');
						        $(".password_im").attr("placeholder","Please enter more than 6 characters");
                                                        $('.cpassword_im').val('');
							$('.cpassword_im').removeClass('inputclass');	
							$('.cpassword_im').addClass('error');
                                                        $(".cpassword_im").attr("placeholder","Confirm Password");
                                                        flg=1;
			 }
			 else
			 {
					 if(pass==cpass && pass != "" )
					 {
						  $('.cpassword_im').removeClass('error');
						   $('.cpassword_im').addClass('inputclass');
						// alert(pass +'--'+ cpass);
					 }
					 else
					 {    
					 		if(pass.length != 0)
							{   $('.cpassword_im').val('');
								$('.cpassword_im').removeClass('inputclass');	
								$('.cpassword_im').addClass('error');
							    $(".cpassword_im").attr("placeholder","Password does not match");
							   flg=1;
							}
					 }
			 }
		//==============================================================================	 
			 
		if(flg==1)
			{
				return false;
			}
		else
		    {
				return true;
			}	
		}
                
                
                
                function Mann_validate_form_field(fromid,getid)
		{       //alert(fromid);
			 
			var txtval =  $.trim($("#"+getid).val());
                        if(txtval==''){
                             $("#"+getid).val('');
                        }
			
			
					if(txtval=="")
					{
						 $("#"+fromid+" #"+getid).removeClass('inputclass');
						  $("#"+fromid+" #"+getid).addClass('error');
						  var msg = $("#"+getid).attr("error_msg");
                                                  $("#"+fromid+" #"+getid).attr("placeholder",'');
						  $("#"+fromid+" #"+getid).attr("placeholder",msg);
						  flg=1;
					}
					else
					{
						 $("#"+fromid+" #"+getid).removeClass('error');
						  $("#"+fromid+" #"+getid).addClass('inputclass');
                                                   flg=0;
				         }
		    
			 
		 
			 
	  

			
		//==============================================================================	 
			 
		if(flg==1)
			{
				return false;
			}
		else
		    {
				return true;
			}	
		}
                
                function Mann_validate_form(fromid)
		{       //alert(fromid);
			var flg=0;
			$("#"+fromid+" .req").each(function() { // alert('dd');
			var txtval =  $.trim($(this).val());
                        
                        if(txtval==''){
                            $(this).val('');
                        }
			 var getid = this.id; 
			
					if(txtval=="")
					{
						 $("#"+fromid+" #"+getid).removeClass('inputclass');
						  $("#"+fromid+" #"+getid).addClass('error');
						  var msg = $("#"+getid).attr("error_msg");
                                                  $("#"+fromid+" #"+getid).attr("placeholder",'');
						  $("#"+fromid+" #"+getid).attr("placeholder",msg);
						  flg=1;
					}
					else
					{
						 $("#"+fromid+" #"+getid).removeClass('error');
						  $("#"+fromid+" #"+getid).addClass('inputclass');
				    }
		     });// end of required
			 
			 $("#"+fromid+" .req1").each(function() {  
			var txtval =   $(this).val();
			 var getid = this.id; 
			//alert(getid);
					if(txtval=="")
					{
						  $("#"+fromid+" #"+getid).removeClass('form-field1');
						  $("#"+fromid+" #"+getid).addClass('form-field1_error');
						  var msg = $("#"+getid).attr("error_msg");
						  $("#"+fromid+" #"+getid).attr("placeholder",msg);
						  flg=1;
						  
						  if(this.id=="gender")
						  {
							  $("#"+fromid+" #"+getid).removeClass('gender_style');
							  $("#"+fromid+" #"+getid).addClass('gender_style_error');
							  flg=1;
						  }
					}
					else
					{
						  $("#"+fromid+" #"+getid).removeClass('form-field1_error');
						  $("#"+fromid+" #"+getid).addClass('form-field1');
						  if(this.id=="gender")
						  {
							  $("#"+fromid+" #"+getid).removeClass('gender_style_error');
							  $("#"+fromid+" #"+getid).addClass('gender_style');
						  }
				    } //alert(flg);	
		     });// end of required
			 
			 $("#"+fromid+" .email").each(function() {
			 var txtval =   $(this).val();
			 var getid = this.id; 
			
					if(txtval=="")
					{
						  $("#"+fromid+" #"+getid).addClass('error');
						  var msg = $("#"+fromid+" #"+getid).attr("error_msg");
						  if(msg=="")
							  {
						               
                                                                    $("#"+fromid+" #"+getid).attr("placeholder","Please Enter Valid E-mail");
                                                                
                                                	  }
							  else
							  {
								     $("#"+fromid+" #"+getid).attr("placeholder",msg);
							  }
                                                flg=1;
					}
					else
					{    
						 var emailvalid =   validateEmail(txtval);
						// alert(emailvalid);
						 if(emailvalid==false)
						 {    $("#"+fromid+" #"+getid).val('');
						 	  $("#"+fromid+" #"+getid).removeClass('inputclass');	
 							  $("#"+fromid+" #"+getid).addClass('error');
                                                          
                                                                    $("#"+fromid+" #"+getid).attr("placeholder","Please Enter Valid E-mail");
                                                                 
                                                         
                                     
                                     
                                                            flg=1;
						 }
						 else
						 {
							  $("#"+fromid+" #"+getid).removeClass('error');
							    $("#"+fromid+" #"+getid).addClass('inputclass');
						 }
				    }
		     });// end of required
			 

			var pass =$.trim($("#"+fromid+" .password_im").val());	 
		        var cpass =$.trim($("#"+fromid+" .cpassword_im").val());	 
			
			 if(( pass.length > 0 &&  pass.length < 6 ))
			 {
				 			 $("#"+fromid+" .password_im").val('');
							$("#"+fromid+" .password_im").removeClass('inputclass');	
							$("#"+fromid+" .password_im").addClass('error');
						   $("#"+fromid+" .password_im").attr("placeholder","Please enter more than 6 characters");
						   
				  			$("#"+fromid+" .cpassword_im").val('');
							$("#"+fromid+" .cpassword_im").removeClass('inputclass');	
							$("#"+fromid+" .cpassword_im").addClass('error');
						   $("#"+fromid+" .cpassword_im").attr("placeholder","Confirm Password");
						  
						   flg=1;
			 }
			 else
			 {
					 if(pass==cpass && pass != "" )
					 {
						  $("#"+fromid+" .cpassword_im").removeClass('error');
						   $("#"+fromid+" .cpassword_im").addClass('inputclass');
						// alert(pass +'--'+ cpass);
					 }
					 else
					 {    
					 		if(pass.length != 0)
							{   $("#"+fromid+" .cpassword_im").val('');
								$("#"+fromid+" .cpassword_im").removeClass('inputclass');	
								$("#"+fromid+" .cpassword_im").addClass('error');
							    $("#"+fromid+" .cpassword_im").attr("placeholder","Password does not match");
							   flg=1;
							}
					 }
			 }
		//==============================================================================	 
			 
		if(flg==1)
			{
				return false;
			}
		else
		    {
				return true;
			}	
		}
		
		function validateEmail(sEmail){
                    var sEmail = $.trim(sEmail);
		var filter = /^([\w-\.]+)@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.)|(([\w-]+\.)+))([a-zA-Z]{2,4}|[0-9]{1,3})(\]?)$/;
		if (filter.test(sEmail)) {
										return true;
									}
									else {
											return false;
									}
			}  /// end of validateemail
		
		
		function isNumberKey(evt,id,lim)
                {  var  getval = $("#"+id).val().length;
	   
	   	if(getval >= lim)
		{   var charCode = (evt.which) ? evt.which : evt.keyCode;
			 if (charCode == 8) 
			{
				return true;
			}
			else	
			{
				return false;
			}
		}
	    else
                {   var charCode = (evt.which) ? evt.which : evt.keyCode;
                          if (charCode > 31 
                    && (charCode < 48 || charCode > 57))
                     return false;


                  return true;
                        }
       } // number only
       
      
    
        
        $(".PlaceMynewmsg").click(function(){
                
                                $.ajax({
                                        type: "POST",
                                        url: "Message_MarkasRead.php",
                                        data:{Subject:'',Dta:''},
                                        success: function(msg){  
                                                
                                                 
                                        },
                                    });  
          
        });
//this is for make left side and center part make same height just call it after page load
function make_same_height(){
        var v1 = $("#sidebar").height();
	var v2 = $("#main-content").height();
	   //     alert(v1+'mamm'+v2);

	var fv;
	if(v2 < v1)
	{ fv=v1+145;
             $("#main-content").css("height",fv); 
	}
	else
	{ fv=v2;
          $("#sidebar").css('height',fv); 
	}
    }     
    
 function make_same_height_afterloaddate(){
        var v1 = $("#PlaceUsersDataHere12").height();
	var v2 = $("#main-content").height();
 
	var fv;
	if(v2 < v1)
	{ fv=v1+145;
             $("#main-content").css("height",fv); 
	}
	else
	{ fv=v2;
          $("#PlaceUsersDataHere12").css('height',fv); 
	}
    }      
    
    
    
    
    
 $(".req").change('keypress', function(e){
					var txtval =   $(this).val();
					var getid = this.id; 
					if(txtval!="")
					{	//alert('hihi');
						$("#"+getid).removeClass('error');
						$("#"+getid).addClass('inputclass');
					}
		});
                
$(".email").change('keypress', function(e){
					var txtval =   $(this).val();
					var getid = this.id; 
					if(txtval!="")
					{
						$("#"+getid).removeClass('error');
						$("#"+getid).addClass('inputclass');
						
					}
		}); 

                
                 function Mediworks_validate()
		{  
			var flg=0;
			$(".req").each(function() { // alert('dd');
			var txtval =  $.trim($(this).val());
                        
                        if(txtval==''){
                            $(this).val('');
                        }
			 var getid = this.id; 
			
					if(txtval=="")
					{
						 $("#"+getid).removeClass('inputclass');
						  $("#"+getid).addClass('error');
						  var msg = $("#"+getid).attr("error_msg");
						  $("#"+getid).attr("placeholder",msg);
						  flg=1;
					}
					else
					{
						 $("#"+getid).removeClass('error');
						  $("#"+getid).addClass('inputclass');
				    }
		     });// end of required
                     
                     
                     
                     $(".murl").each(function() { // alert('dd');
			var txtval =  $.trim($(this).val());
                        
                        if(txtval==''){
                            $(this).val('');
                        }
			 var getid = this.id; 
			
					if(txtval=="")
					{
						  
					}
					else
					{
                                                 var x =  validateURL(txtval);
                                                 if(x==false){
                                                        $("#"+getid).removeClass('inputclass');
                                                        $("#"+getid).addClass('error');
                                                        var msg = "http://www.sitename.com";// $("#"+getid).attr(error_msg);
                                                        $("#"+getid).attr("placeholder",msg);
                                                        flg=1;
                                                 }else{
                                                     $("#"+getid).removeClass('error');
						     $("#"+getid).addClass('inputclass');
                                                 }
						  
				    }
		     });// end of required
                     $(".reqfile").each(function() { // alert('dd');
			var txtval =  $.trim($(this).val());
                        
                        if(txtval==''){
                            $(this).val('');
                        }
			 var getid = this.id; 
			
					if(txtval=="")
					{
						  //$(".btn-white").removeClass('inputclass');
						  $(".btn-white").addClass('error');
						  //var msg = $("#"+getid).attr("error_msg");
						  //$("#"+getid).attr("placeholder",msg);
						  flg=1;
					}
					else
					{
						  $(".btn-white").removeClass('error');
						  //$(".btn-white").addClass('inputclass');
				    }
		     });// end of file required
			 
                        
                         
                         
                         
			 $(".req1").each(function() {  
			var txtval =   $(this).val();
			 var getid = this.id; 
			//alert(getid);
					if(txtval=="")
					{
						  $("#"+getid).removeClass('form-field1');
						  $("#"+getid).addClass('form-field1_error');
						  var msg = $("#"+getid).attr("error_msg");
						  $("#"+getid).attr("placeholder",msg);
						  flg=1;
						  
						  if(this.id=="gender")
						  {
							  $("#"+getid).removeClass('gender_style');
							  $("#"+getid).addClass('gender_style_error');
							  flg=1;
						  }
					}
					else
					{
						  $("#"+getid).removeClass('form-field1_error');
						  $("#"+getid).addClass('form-field1');
						  if(this.id=="gender")
						  {
							  $("#"+getid).removeClass('gender_style_error');
							  $("#"+getid).addClass('gender_style');
						  }
				    } //alert(flg);	
		     });// end of required
			 
			 $(".email").each(function() {
			 var txtval =   $(this).val();
			 var getid = this.id; 
			
					if(txtval=="")
					{
						 
						  $("#"+getid).addClass('error');
						  var msg = $("#"+getid).attr("error_msg");
						  if(msg=="")
							  {
								    
                                                                         $("#"+getid).attr("placeholder","Please Enter Valid E-mail");
                                                                     
							  }
							  else
							  {
								     $("#"+getid).attr("placeholder",msg);
							  }
						 
						  flg=1;
					}
					else
					{    
						 var emailvalid =   validateEmail(txtval);
						// alert(emailvalid);
						 if(emailvalid==false)
						 {    $("#"+getid).val('');
						 	  $("#"+getid).removeClass('inputclass');	
 							  $("#"+getid).addClass('error');
							 
								    
                                                                         $("#"+getid).attr("placeholder","Please Enter Valid E-mail");
                                                                    
							  
							  flg=1;
						 }
						 else
						 {
							  $("#"+getid).removeClass('error');
							    $("#"+getid).addClass('inputclass');
						 }
				    }
		     });// end of required
			 

			var pass =$.trim($(".password_im").val());	 
		        var cpass =$.trim($(".cpassword_im").val());	 
			
			 if(( pass.length > 0 &&  pass.length < 6 ))
			 {
				 			 $('.password_im').val('');
							$('.password_im').removeClass('inputclass');	
							$('.password_im').addClass('error');
						   $(".password_im").attr("placeholder","Please enter more than 6 characters");
						   
				  			$('.cpassword_im').val('');
							$('.cpassword_im').removeClass('inputclass');	
							$('.cpassword_im').addClass('error');
						   $(".cpassword_im").attr("placeholder","Confirm Password");
						  
						   flg=1;
			 }
			 else
			 {
					 if(pass==cpass && pass != "" )
					 {
						  $('.cpassword_im').removeClass('error');
						   $('.cpassword_im').addClass('inputclass');
						// alert(pass +'--'+ cpass);
					 }
					 else
					 {    
					 		if(pass.length != 0)
							{   $('.cpassword_im').val('');
								$('.cpassword_im').removeClass('inputclass');	
								$('.cpassword_im').addClass('error');
							    $(".cpassword_im").attr("placeholder","Password does not match");
							   flg=1;
							}
					 }
			 }
		//==============================================================================	 
			 
		if(flg==1)
			{
				return false;
			}
		else
		    {
				return true;
			}	
		}
                
                function Mann_validate_form(fromid)
		{       //alert(fromid);
			var flg=0;
			$("#"+fromid+" .req").each(function() { // alert('dd');
			var txtval =  $.trim($(this).val());
                        
                        if(txtval==''){
                            $(this).val('');
                        }
			 var getid = this.id; 
			
					if(txtval=="")
					{
						 $("#"+fromid+" #"+getid).removeClass('inputclass');
						  $("#"+fromid+" #"+getid).addClass('error');
						  var msg = $("#"+getid).attr("error_msg");
						  $("#"+fromid+" #"+getid).attr("placeholder",msg);
						  flg=1;
					}
					else
					{
						 $("#"+fromid+" #"+getid).removeClass('error');
						  $("#"+fromid+" #"+getid).addClass('inputclass');
				    }
		     });// end of required
                     
                     
                     
                     $("#"+fromid+" .murl").each(function() { // alert('dd');
			var txtval =  $.trim($(this).val());
                        
                        if(txtval==''){
                            $(this).val('');
                        }
			 var getid = this.id; 
			
					if(txtval=="")
					{
						 
					}
					else
					{        var x =  validateURL(txtval);  
                                                 if(x==false){
                                                     $("#"+fromid+" #"+getid).val('');
                                                     $("#"+fromid+" #"+getid).removeClass('inputclass');
                                                    $("#"+fromid+" #"+getid).addClass('error');
                                                    var msg = "http://www.sitename.com";// $("#"+getid).attr(error_msg);
                                                    $("#"+fromid+" #"+getid).attr("placeholder",msg);
                                                    flg=1;
                                                 }else{
                                                        $("#"+fromid+" #"+getid).removeClass('error');
                                                        $("#"+fromid+" #"+getid).addClass('inputclass');
                                                 }
                                                
						 
				    }
		     });// end of required
			 
			 $("#"+fromid+" .req1").each(function() {  
			var txtval =   $(this).val();
			 var getid = this.id; 
			//alert(getid);
					if(txtval=="")
					{
						  $("#"+fromid+" #"+getid).removeClass('form-field1');
						  $("#"+fromid+" #"+getid).addClass('form-field1_error');
						  var msg = $("#"+getid).attr("error_msg");
						  $("#"+fromid+" #"+getid).attr("placeholder",msg);
						  flg=1;
						  
						  if(this.id=="gender")
						  {
							  $("#"+fromid+" #"+getid).removeClass('gender_style');
							  $("#"+fromid+" #"+getid).addClass('gender_style_error');
							  flg=1;
						  }
					}
					else
					{
						  $("#"+fromid+" #"+getid).removeClass('form-field1_error');
						  $("#"+fromid+" #"+getid).addClass('form-field1');
						  if(this.id=="gender")
						  {
							  $("#"+fromid+" #"+getid).removeClass('gender_style_error');
							  $("#"+fromid+" #"+getid).addClass('gender_style');
						  }
				    } //alert(flg);	
		     });// end of required
			 
                         
                          $("#"+fromid+" .reqfile").each(function() {   
			var txtval =  $.trim($(this).val());
                         if(txtval==''){
                            $(this).val('');
                        }
			 var getid = this.id; 
			
					if(txtval=="")
					{
						  //$(".btn-white").removeClass('inputclass');
						  $("#"+fromid+" .btn-white").addClass('error');
						  //var msg = $("#"+getid).attr("error_msg");
						  //$("#"+getid).attr("placeholder",msg);
						  flg=1;
					}
					else
					{
						  $("#"+fromid+" .btn-white").removeClass('error');
						  //$(".btn-white").addClass('inputclass');
				    }
		     });// end of required
                     
                     
                     
                     
                     
                  
                     
                     
			 $("#"+fromid+" .email").each(function() {
			 var txtval =   $(this).val();
			 var getid = this.id; 
			
					if(txtval=="")
					{
						 
						  $("#"+fromid+" #"+getid).addClass('error');
						  var msg = $("#"+fromid+" #"+getid).attr("error_msg");
						  if(msg=="")
							  {
								    
                                                                        $("#"+fromid+" #"+getid).attr("placeholder","Please Enter Valid E-mail");
                                                                      
                                                                   
							  }
							  else
							  {
								     $("#"+fromid+" #"+getid).attr("placeholder",msg);
							  }
						 
						  flg=1;
					}
					else
					{    
						 var emailvalid =   validateEmail(txtval);
						// alert(emailvalid);
						 if(emailvalid==false)
						 {    $("#"+fromid+" #"+getid).val('');
						 	  $("#"+fromid+" #"+getid).removeClass('inputclass');	
 							  $("#"+fromid+" #"+getid).addClass('error');
							 
								   
                                                                    
                                                                        $("#"+fromid+" #"+getid).attr("placeholder","Please Enter Valid E-mail");
                                                                      
							  
							  flg=1;
						 }
						 else
						 {
							  $("#"+fromid+" #"+getid).removeClass('error');
							    $("#"+fromid+" #"+getid).addClass('inputclass');
						 }
				    }
		     });// end of required
			 

			var pass =$.trim($("#"+fromid+" .password_im").val());	 
		        var cpass =$.trim($("#"+fromid+" .cpassword_im").val());	 
			
			 if(( pass.length > 0 &&  pass.length < 6 ))
			 {
				 			 $("#"+fromid+" .password_im").val('');
							$("#"+fromid+" .password_im").removeClass('inputclass');	
							$("#"+fromid+" .password_im").addClass('error');
						   $("#"+fromid+" .password_im").attr("placeholder","Please enter more than 6 characters");
						   
				  			$("#"+fromid+" .cpassword_im").val('');
							$("#"+fromid+" .cpassword_im").removeClass('inputclass');	
							$("#"+fromid+" .cpassword_im").addClass('error');
						   $("#"+fromid+" .cpassword_im").attr("placeholder","Confirm Password");
						  
						   flg=1;
			 }
			 else
			 {
					 if(pass==cpass && pass != "" )
					 {
						  $("#"+fromid+" .cpassword_im").removeClass('error');
						   $("#"+fromid+" .cpassword_im").addClass('inputclass');
						// alert(pass +'--'+ cpass);
					 }
					 else
					 {    
					 		if(pass.length != 0)
							{   $("#"+fromid+" .cpassword_im").val('');
								$("#"+fromid+" .cpassword_im").removeClass('inputclass');	
								$("#"+fromid+" .cpassword_im").addClass('error');
							    $("#"+fromid+" .cpassword_im").attr("placeholder","Password does not match");
							   flg=1;
							}
					 }
			 }
		//==============================================================================	 
			 
		if(flg==1)
			{
				return false;
			}
		else
		    {
				return true;
			}	
		}
		
		function validateEmail(sEmail) {
                    var sEmail = $.trim(sEmail);
		//var filter = /^([\w-\.]+)@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.)|(([\w-]+\.)+))([a-zA-Z]{2,4}|[0-9]{1,3})(\]?)$/;
                var filter = /^[-a-z0-9~!$%^&*_=+}{\'?]+(\.[-a-z0-9~!$%^&*_=+}{\'?]+)*@([a-z0-9_][-a-z0-9_]*(\.[-a-z0-9_]+)*\.(aero|arpa|biz|com|coop|edu|gov|info|int|mil|museum|name|net|org|pro|travel|mobi|[a-z][a-z])|([0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}))(:[0-9]{1,5})?$/i;
                    
                    if (filter.test(sEmail)) {
										return true;
									}
									else {
											return false;
									}
			}  /// end of validateemail
		
		
		function isNumberKey(evt,id,lim)
                {  var  getval = $("#"+id).val().length;
	   
	   	if(getval >= lim)
		{   var charCode = (evt.which) ? evt.which : evt.keyCode;
			 if (charCode == 8) 
			{
				return true;
			}
			else	
			{
				return false;
			}
		}
	    else
                {   var charCode = (evt.which) ? evt.which : evt.keyCode;
                          if (charCode > 31 
                    && (charCode < 48 || charCode > 57))
                     return false;


                  return true;
                        }
       } // number only
       
      
 
        $(".PlaceMynewmsg").click(function(){
                
                                $.ajax({
                                        type: "POST",
                                        url: "Message_MarkasRead.php",
                                        data:{Subject:'',Dta:''},
                                        success: function(msg){  
                                                
                                                 
                                        },
                                    });  
          
        });


function validateURL(textval) {
      var urlregex = new RegExp(
            "^(http|https|ftp)\://([a-zA-Z0-9\.\-]+(\:[a-zA-Z0-9\.&amp;%\$\-]+)*@)*((25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[1-9])\.(25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[1-9]|0)\.(25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[1-9]|0)\.(25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[0-9])|([a-zA-Z0-9\-]+\.)*[a-zA-Z0-9\-]+\.(com|edu|gov|int|mil|net|org|biz|arpa|info|name|pro|aero|coop|museum|[a-zA-Z]{2}))(\:[0-9]+)*(/($|[a-zA-Z0-9\.\,\?\'\\\+&amp;%\$#\=~_\-]+))*$");
      return urlregex.test(textval);
    }
    
    function onedot_number(evt,myval)
       {
		   var charCode = (evt.which) ? evt.which : event.keyCode;
		     if(charCode == 8 || charCode == 0){
                return true;
            }
            if(charCode < 46 || charCode > 59) {
                return false;
                //event.preventDefault();
            } // prevent if not number/dot

            if(charCode == 46 && myval.indexOf('.') != -1) {
                return false;
            } // prevent if already dot
       }