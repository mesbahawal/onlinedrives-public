function change_order_icon(name, number, type) {
    var dir;
    var className = document.getElementById('col_' + name).className;

    var allChildren = document.getElementById('table').getElementsByTagName('th');
    for (var i = 0; i < allChildren.length; i++) {
        var obj = allChildren[i];
        if (obj.getElementsByTagName('span')[1]) {
            obj.getElementsByTagName('span')[1].className = 'abs';
        }
    }

    if (className == 'abs glyphicon glyphicon-chevron-up') {
        document.getElementById('col_' + name).className = 'abs glyphicon glyphicon-chevron-down';
        dir = 'desc';
    }
    else {
        document.getElementById('col_' + name).className = 'abs glyphicon glyphicon-chevron-up';
        dir = 'asc';
    }

    sortTableFile(number, type, dir);
    sortTableFolder(number, type, dir);
}

function sortTableFolder(n, type, dir) {
    var table, rows, switching, i, x, y, shouldSwitch, dir, switchcount = 0;
    table = document.getElementById("table");
    switching = true;
    // Set the sorting direction to ascending:
    //dir = "asc";
    /* Make a loop that will continue until
    no switching has been done: */
    while (switching) {
        // Start by saying: no switching is done:
        switching = false;
        rows = table.rows;
        /* Loop through all table rows (except the
        first, which contains table headers): */
        for (i = 1; i < (rows.length - 1); i++) {
            // Start by saying there should be no switching:
            shouldSwitch = false;
            /* Get the two elements you want to compare,
            one from current row and one from the next: */
            restype1 = rows[i].getElementsByTagName("TD")[0].innerHTML;
            restype2 = rows[i + 1].getElementsByTagName("TD")[0].innerHTML;

            if (restype1 == "folder" && restype2 == "folder") {
                var check_for_name = rows[i].getElementsByTagName("TD")[n].getElementsByTagName("A")[0];
                var check_for_modified = rows[i].getElementsByTagName("TD")[n];
                var check_for_service = rows[i].getElementsByTagName("TD")[n].getElementsByTagName("IMG")[0];
                if (check_for_name) {
                    x = rows[i].getElementsByTagName("TD")[n].getElementsByTagName("A")[0].innerHTML;
                    y = rows[i + 1].getElementsByTagName("TD")[n].getElementsByTagName("A")[0].innerHTML;
                }
                else if (check_for_service) {
                    x = rows[i].getElementsByTagName("TD")[n].getElementsByTagName("IMG")[0].title;
                    y = rows[i + 1].getElementsByTagName("TD")[n].getElementsByTagName("IMG")[0].title;
                }
                else if (check_for_modified) {
                    x = rows[i].getElementsByTagName("TD")[n - 1].innerHTML;
                    y = rows[i + 1].getElementsByTagName("TD")[n - 1].innerHTML;
                }
                /* Check if the two rows should switch place,
                based on the direction, asc or desc: */
                if (dir == "asc" && type == "T") {
                    if (x.toLowerCase() > y.toLowerCase()) {
                        // If so, mark as a switch and break the loop:
                        shouldSwitch = true;
                        break;
                    }
                }
                else if (dir == "desc" && type == "T") {
                    if (x.toLowerCase() < y.toLowerCase()) {
                        // If so, mark as a switch and break the loop:
                        shouldSwitch = true;
                        break;
                    }
                }
                else if (dir == "asc" && type == "N") {
                    if (Number(x) < Number(y)) {
                        // If so, mark as a switch and break the loop:
                        shouldSwitch = true;
                        break;
                    }
                }
                else if (dir == "desc" && type == "N") {
                    if (Number(x) > Number(y)) {
                        // If so, mark as a switch and break the loop:
                        shouldSwitch = true;
                        break;
                    }
                }
            }
        }
        if (shouldSwitch) {
            /* If a switch has been marked, make the switch
            and mark that a switch has been done: */
            rows[i].parentNode.insertBefore(rows[i + 1], rows[i]);
            switching = true;
            // Each time a switch is done, increase this count by 1:
            switchcount++; 
        }
        else {
            /* If no switching has been done AND the direction is "asc",
            set the direction to "desc" and run the while loop again. */
            //if (switchcount == 0 && dir == "asc") {
                //dir = "desc";
                switching = false;
            //}
        }
    }
}

function sortTableFile(n, type, dir) {
    var table, rows, switching, i, x, y, shouldSwitch, dir, switchcount = 0;
    table = document.getElementById("table");
    switching = true;
    // Set the sorting direction to ascending:
    //dir = "asc";
    /* Make a loop that will continue until
    no switching has been done: */
    while (switching) {
        // Start by saying: no switching is done:
        switching = false;
        rows = table.rows;
        /* Loop through all table rows (except the
        first, which contains table headers): */
        for (i = 1; i < (rows.length - 1); i++) {
            // Start by saying there should be no switching:
            shouldSwitch = false;
            /* Get the two elements you want to compare,
            one from current row and one from the next: */
            restype = rows[i].getElementsByTagName("TD")[0].innerHTML;

            var check_for_name = rows[i].getElementsByTagName("TD")[n].getElementsByTagName("A")[0];
            var check_for_modified = rows[i].getElementsByTagName("TD")[n];
            var check_for_service = rows[i].getElementsByTagName("TD")[n].getElementsByTagName("IMG")[0];
            if (check_for_name) {
                x = rows[i].getElementsByTagName("TD")[n].getElementsByTagName("A")[0].innerHTML;
                y = rows[i + 1].getElementsByTagName("TD")[n].getElementsByTagName("A")[0].innerHTML;
            }
            else if (check_for_service) {
                x = rows[i].getElementsByTagName("TD")[n].getElementsByTagName("IMG")[0].title;
                y = rows[i + 1].getElementsByTagName("TD")[n].getElementsByTagName("IMG")[0].title;
            }
            else if (check_for_modified) {
                x = rows[i].getElementsByTagName("TD")[n - 1].innerHTML;
                y = rows[i + 1].getElementsByTagName("TD")[n - 1].innerHTML;
            }
            /* Check if the two rows should switch place,
            based on the direction, asc or desc: */
            if (dir == "asc" && type == "T" && restype == "file") {
                if (x.toLowerCase() > y.toLowerCase() && restype != "folder") {
                    // If so, mark as a switch and break the loop:
                    shouldSwitch = true;
                    break;
                }
            }
            else if (dir == "desc" && type == "T" && restype == "file") {
                if (x.toLowerCase() < y.toLowerCase() && restype != "folder") {
                    // If so, mark as a switch and break the loop:
                    shouldSwitch = true;
                    break;
                }
            }
            else if (dir == "asc" && type == "N" && restype == "file") {
                if (Number(x) < Number(y)) {
                    // If so, mark as a switch and break the loop:
                    shouldSwitch = true;
                    break;
                }
            }
            else if (dir == "desc" && type == "N" && restype == "file") {
                if (Number(x) > Number(y)) {
                    // If so, mark as a switch and break the loop:
                    shouldSwitch = true;
                    break;
                }
            }
        }
        if (shouldSwitch) {
            /* If a switch has been marked, make the switch
            and mark that a switch has been done: */
            rows[i].parentNode.insertBefore(rows[i + 1], rows[i]);
            switching = true;
            // Each time a switch is done, increase this count by 1:
            switchcount++; 
        }
        else {
            /* If no switching has been done AND the direction is "asc",
            set the direction to "desc" and run the while loop again. */
            //if (switchcount == 0 && dir == "asc") {
                //dir = "desc";
                switching = false;
            //}
        }
    }
}
