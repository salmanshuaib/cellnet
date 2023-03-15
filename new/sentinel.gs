//v0.0.1
function autoexecute() {
  // Call the sort function to sort the "messages" sheet
  sortSheet();
  
  // The code to execute every 1 minutes goes here
  updateSheet();
}  

function updateSheet() {
  var spreadsheetName = "9LEGIONS"; // Change to the name of your spreadsheet
  var sheetName = "messages"; // Change to the name of your sheet
  var resultsSheetName = "results"; // Change to the name of your results sheet
  var spreadsheet = SpreadsheetApp.getActiveSpreadsheet();
  var sheet = spreadsheet.getSheetByName(sheetName);
  var range = sheet.getDataRange().getValues();

  // Set the column labels for the messages sheet
  sheet.getRange(1, 1).setValue("DATE").setFontWeight('bold').setHorizontalAlignment("center");
  sheet.getRange(1, 2).setValue("MESSAGE").setFontWeight('bold').setHorizontalAlignment("center");

  // set column A width to 200 Pixels
  sheet.setColumnWidth(1, 200);

  // Clear the sheet if there are 33 or more entries
  if (range.length >= 33) {
    sheet.clearContent();
  }

  // Set the column labels for the "results" sheet
  var resultsSheet = spreadsheet.getSheetByName(resultsSheetName);
  resultsSheet.getRange("A1").setValue("LEGION").setFontWeight('bold');
  resultsSheet.getRange("B1").setValue("SCORE").setFontWeight('bold');
  // Set horizontal alignment for column A and B
  resultsSheet.getRange("A1:B1").setHorizontalAlignment("center");

  // Initialize legionScores array to zero
  var legionScores = Array(9).fill(0);

  // Check if messages sheet is empty
  if (range.length > 0) {
    for (var i = 0; i < range.length; i++) {
      if (i === 0) {
        continue; // skip first row
      }
      if (range[i][0].length > 0) {
        var firstLetter = range[i][1].charAt(0).toLowerCase();
        var digit = resolute(firstLetter);

        // Add the occurrence of the digit to the corresponding Legion's score
        legionScores[digit - 1]++;
      }
    }
  }

  // Set legion titles and scores in the results sheet
  for (var i = 0; i < 9; i++) {
    var legionTitle = "Legion " + (i+1);
    resultsSheet.getRange(i+2, 1).setValue(legionTitle);
    resultsSheet.getRange(i+2, 2).setValue(legionScores[i]);
  }

  // Determine the highest score and set the background color of the corresponding cell(s) to Yellow
  var maxScore = Math.max(...legionScores);
  
  // Clear the background color of all cells in the results sheet excepting the first row
resultsSheet.getRange(2, 1, resultsSheet.getLastRow()-1, resultsSheet.getLastColumn()).clearFormat();
  
  for (var i = 0; i < 9; i++) {
    if (legionScores[i] === maxScore) {
      resultsSheet.getRange(i+2, 1, 1, 2).setBackground("yellow");
    }
  }
}


function resolute(name) {
  var charCode = name.charCodeAt(0);
  if (charCode < 97 || charCode > 122) {
    // Return 9 if the character is not one of the 26 letters of the English alphabet
    return 9;
  } else {
    var initial_value = charCode - 96;
    while (initial_value > 9) {
      var digits = Array.from(String(initial_value), Number);
      initial_value = digits.reduce(function(a, b) { return a + b; });
    }
    return initial_value;
  }
}

function sortSheet() {
  var spreadsheetName = "9LEGIONS"; // Change to the name of your spreadsheet
  var sheetName = "messages"; // Change to the name of your sheet
  var spreadsheet = SpreadsheetApp.getActiveSpreadsheet();
  var sheet = spreadsheet.getSheetByName(sheetName);
  var range = sheet.getRange(2, 1, sheet.getLastRow() - 1, sheet.getLastColumn());
  var values = range.getValues();

  // Sort the range in descending order based on the "DATE" column
  values.sort(function(a, b) {
    var dateA = parseDate(a[0]);
    var dateB = parseDate(b[0]);
    return dateB - dateA;
  });

  // Update the sorted range on the sheet
  range.setValues(values);
}

function parseDate(dateStr) {
  var dateParts = dateStr.split(" ");
  var month = parseMonth(dateParts[0]);
  var day = parseInt(dateParts[1].replace(",", ""));
  var year = parseInt(dateParts[2]);
  var timeParts = dateParts[4].split(":");
  var hour = parseInt(timeParts[0]);
  var minute = parseInt(timeParts[1]);
  if (dateParts[5] == "PM") {
    hour += 12;
  }
  var dateObj = new Date(year, month, day, hour, minute);
  return dateObj;
}

function parseMonth(monthStr) {
  var monthNames = [    "January", "February", "March", "April", "May", "June",    "July", "August", "September", "October", "November", "December"  ];
  return monthNames.indexOf(monthStr);
}




function setTrigger() {
  // Get all existing triggers in the project
  var triggers = ScriptApp.getProjectTriggers();

  // Check if a trigger already exists for the autoexecute function
  var triggerExists = false;
  for (var i = 0; i < triggers.length; i++) {
    if (triggers[i].getHandlerFunction() == 'autoexecute') {
      triggerExists = true;
      break;
    }
  }

  // If a trigger doesn't exist create a new one that runs the autoexecute function every 1 minutes
  if (!triggerExists) {
    ScriptApp.newTrigger('autoexecute')
             .timeBased()
             .everyMinutes(1)
             .create();
  }
}