
/*******************************************************
* Random Password Generator JavaScript
* <http://www.kipp.smith.net/javascripts/random.htm/>
* This script is free as long as original credits remain.
********************************************************/

// Use the following variables for password characters
// and length

  var characters="0123456789abcdefghijklmnopqrstuvwxyz";

  var passwordlength=0;

function generatepassword(object, plength) {
// This function will build a string using randomly
// generated characters.

  var password = "";
  var n = 0;
  var randomnumber = 0;
  passwordlength=plength;
  while( n < passwordlength ) {
     n ++;
     randomnumber = Math.floor(characters.length*Math.random());
     password += characters.substring(randomnumber,randomnumber + 1);
  }

// Display the word inside the form text box

  object.value = password;
}

