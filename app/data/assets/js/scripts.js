/**
 * Scripts for pop-spider
 */

var displayResults = function(id) {
    var div = document.getElementById(id);
    if (div != null) {
        if (div.style.display == 'none') {
            div.style.display = 'table-row';
        } else {
            div.style.display = 'none';
        }
    }
};