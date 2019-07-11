var isAlreadyClick = false;

page_result_final = '';

function capitalizeFirstLetter(theString) {
    return theString && theString[0].toUpperCase() + theString.slice(1);
}

function whatIsTheDivStatus(theDiv, divStatus) {
    if ($j('#' + theDiv).css('display') == 'none') {
        $j('#' + divStatus).html('<i class="fa fa-minus-square"></i>');
    } else {
        $j('#' + divStatus).html('<i class="fa fa-plus-square"></i>');
    }
}


function resetInlineDisplay(theDiv) {
    setTimeout(function () {
        if ($(theDiv).style.display == '')
            $(theDiv).style.display = 'inline';
    }, 250);
}

function changeSignatureForProfil(selected, mailSignaturesJS) {
    var nb = selected.getAttribute('data-nb');
    var body = $('emailSignature_ifr').contentWindow.document.getElementById("tinymce");
    if (nb >= 0) {
        body.innerHTML = mailSignaturesJS[nb].signature;
        $('trashButton').style.display = '';
        $('signatureTitle').disabled = true;
        $('signatureTitle').className = "readonly";
    } else {
        body.innerHTML = '';
        $('trashButton').style.display = 'none';
        $('signatureTitle').disabled = false;
        $('signatureTitle').className = "";
    }
}

function deleteSignature(mailSignaturesJS) {
    var rep = confirm("Confirmation de suppression ?");

    var select = $("selectSignatures");
    var selectedIndex = select.options[select.selectedIndex];
    var nb = selectedIndex.getAttribute("data-nb");
    if (nb >= 0 && rep) {
        var path_manage_script = "index.php?display=true&page=mailSignatureManagement";
        new Ajax.Request(path_manage_script, {
            method: "POST",
            parameters: {
                action: "DEL",
                idToDelete: mailSignaturesJS[nb].id
            },
            onSuccess: function (answer) {
                if (answer.responseText == "success") {
                    selectedIndex.style.display = 'none';
                    select.selectedIndex = 0;
                    changeSignatureForProfil(select.options[0], mailSignaturesJS);
                }
            }
        });
    }
}

function addNewRowPriority(buttonRow) {
    var index = buttonRow.rowIndex;
    var indexDiff = index - $("priorityAddField").rowIndex;

    if ($("priorityAddField").style.display == "none") {
        $("priorityAddField").style.display = "";
        $("minusButton").style.display = "";
    } else {
        var newRow = $("prioritiesTable").insertRow(index);
        newRow.innerHTML = "<td align='left'><input name='label_new" + indexDiff + "' id='label_new" + indexDiff + "' placeholder='Nom priorité' size='18'> <input style='background:none;border:none;width:45px;' name='color_new" + indexDiff + "' id='color_new" + indexDiff + "' type='color' value=''></td>" +
            "<td align='left'><input name='priority_new" + indexDiff + "' id='priority_new" + indexDiff + "' size='6' value='*'></td>" +
            "<td align='left'><select name='working_new" + indexDiff + "' id='working_new" + indexDiff + "'><option value='true'>Jours ouvrés</option><option value='false' >Jours calendaires</option></select></td>";
    }

}

function delNewRowPriority(buttonRow) {
    var index = buttonRow.rowIndex;
    var indexDiff = index - $("priorityAddField").rowIndex;

    if (indexDiff <= 1) {
        $("priorityAddField").style.display = "none";
        $("minusButton").style.display = "none";
    } else {
        $("prioritiesTable").deleteRow(index - 1);
    }

}

function deletePriority(rowToDelete) {
    var rep = confirm("Confirmation de suppression ?");
    var index = rowToDelete.getAttribute("data-index");

    if (index >= 0 && rep) {
        var path_manage_script = "index.php?page=priorityManager&admin=priorities";
        new Ajax.Request(path_manage_script, {
            method: "POST",
            parameters: {
                mode: "delete",
                indexToDelete: index
            },
            onSuccess: function () {
                window.top.location.reload();
            }
        });
    }
}

function repost(php_file, update_divs, fields, action, timeout) {
    var event_count = 0;

    //Observe fields
    for (var i = 0; i < fields.length; ++i) {
        $(fields[i]).observe(action, send);
    }

    function send(event) {
        var params = '';
        event_count++;

        for (var i = 0; i < fields.length; ++i) {
            params += $(fields[i]).serialize() + '&';
        }

        setTimeout(function () {
            event_count--;

            if (event_count == 0)
                new Ajax.Request(php_file, {
                    method: 'post',
                    onSuccess: function (transport) {

                        var response = transport.responseText;
                        var reponse_div = new Element("div");
                        reponse_div.innerHTML = response;
                        var replace_div = reponse_div.select('div');

                        for (var i = 0; i < replace_div.length; ++i)
                            for (var j = 0; j < update_divs.length; ++j) {
                                if (replace_div[i].id == update_divs[j])
                                    $(update_divs[j]).replace(replace_div[i]);
                            }
                    },
                    onFailure: function () {
                        alert('Something went wrong...');
                    },
                    parameters: params
                });
        }, timeout);
    }
}

/**
 * List used for autocompletion
 *
 */
var initList = function (idField, idList, theUrlToListScript, paramNameSrv, minCharsSrv) {
    new Ajax.Autocompleter(
        idField,
        idList,
        theUrlToListScript, {
            paramName: paramNameSrv,
            minChars: minCharsSrv
        });
};

/**
 * List used for autocompletion and set id in hidden input
 *
 */
var initList_hidden_input = function (idField, idList, theUrlToListScript, paramNameSrv, minCharsSrv, new_value) {
    new Ajax.Autocompleter(
        idField,
        idList,
        theUrlToListScript, {
            paramName: paramNameSrv,
            minChars: minCharsSrv,
            afterUpdateElement: function (text, li) {
                $j('#' + new_value).val(li.id);
            }
        });
};

var initList_hidden_input2 = function (idField, idList, theUrlToListScript, paramNameSrv, minCharsSrv, new_value, actual_value) {
    new Ajax.Autocompleter(
        idField,
        idList,
        theUrlToListScript, {
            paramName: paramNameSrv,
            minChars: minCharsSrv,
            afterUpdateElement: function (text, li) {
                var str = li.id;
                var res = str.split(",");
                $j("#" + new_value).val(res[1]);
                $j("#" + actual_value).val(res[0]);
                $j('#country').val('FRANCE');
            }
        });
};

var initList_hidden_input3 = function (idField, idList, theUrlToListScript, paramNameSrv, minCharsSrv, new_value, actual_value) {
    new Ajax.Autocompleter(
        idField,
        idList,
        theUrlToListScript, {
            paramName: paramNameSrv,
            minChars: minCharsSrv,
            afterUpdateElement: function (text, li) {
                var str = li.id;
                var res = str.split(",");
                $j("#" + new_value).val(res[0]);
                $j("#" + actual_value).val(res[1]);
                $j('#country').val('FRANCE');
            }
        });
};

var initList_hidden_input_before = function (idField, idList, theUrlToListScript, paramNameSrv, minCharsSrv, new_value, previous_name, previous_field) {
    new Ajax.Autocompleter(
        idField,
        idList,
        theUrlToListScript, {
            paramName: paramNameSrv,
            minChars: minCharsSrv,
            callback: function (element, entry) {
                return entry + "&" + previous_name + "=" + $j("#" + previous_field).val();
            },
            afterUpdateElement: function (text, li) {
                $j("#" + new_value).val(li.id);
            }
        });
};


/*********** Init vars for the calendar ****************/
var allMonth = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
var allNameOfWeekDays = ["Lu", "Ma", "Me", "Je", "Ve", "Sa", "Di"];
var allNameOfMonths = ["Janvier", "Fevrier", "Mars", "Avril", "Mai", "Juin", "Juillet", "Aout", "Septembre", "Octobre", "Novembre", "Decembre"];
var newDate = new Date();
var yearZero = newDate.getFullYear();
var monthZero = newDate.getMonth();
var day = newDate.getDate();
var currentDay = 0,
    currentDayZero = 0;
var month = monthZero,
    year = yearZero;
var yearMin = 1910,
    yearMax = 2060;
var target = '';
var hoverEle = false;
/***************************************

/***********Functions used by the calendar ****************/
function setTarget(e) {
    if (e) return e.target;
    if (event) return event.srcElement;
}

function newElement(type, attrs, content, toNode) {
    var ele = document.createElement(type);

    if (attrs) {
        for (var i = 0; i < attrs.length; i++) {
            eval('ele.' + attrs[i][0] + (attrs[i][2] ? '=\u0027' : '=') + attrs[i][1] + (attrs[i][2] ? '\u0027' : ''));
        }
    }
    if (content) ele.appendChild(document.createTextNode(content));
    if (toNode) toNode.appendChild(ele);
    return ele;
}

function setMonth(ele) {
    month = parseInt(ele.value);
    calender()
}

function setYear(ele) {
    year = parseInt(ele.value);
    calender()
}

function setValue(ele) {
    if (ele.parentNode.className == 'week' && ele.firstChild) {
        var dayOut = ele.firstChild.nodeValue;
        if (dayOut < 10) dayOut = '0' + dayOut;
        var monthOut = month + 1;
        if (monthOut < 10) monthOut = '0' + monthOut;
        target.value = dayOut + '-' + monthOut + '-' + year;

        target.focus();
        removeCalender();
    }
}

function removeCalender() {
    var parentEle = $("calender");
    while (parentEle.firstChild) parentEle.removeChild(parentEle.firstChild);
    $('basis').parentNode.removeChild($('basis'));
}

function calender() {
    var parentEle = $("calender");
    parentEle.onmouseover = function (e) {
        var ele = setTarget(e);
        if (ele.parentNode.className == 'week' && ele.firstChild && ele != hoverEle) {
            if (hoverEle) hoverEle.className = hoverEle.className.replace(/hoverEle ?/, '');
            hoverEle = ele;
            ele.className = 'hoverEle ' + ele.className;
        } else {
            if (hoverEle) {
                hoverEle.className = hoverEle.className.replace(/hoverEle ?/, '');
                hoverEle = false;
            }
        }
    };
    while (parentEle.firstChild) parentEle.removeChild(parentEle.firstChild);

    function check() {
        if (year % 4 == 0 && (year % 100 != 0 || year % 400 == 0)) allMonth[1] = 29;
        else allMonth[1] = 28;
    }

    function addClass(name) {
        if (!currentClass) {
            currentClass = name
        } else {
            currentClass += ' ' + name
        }
    };
    if (month < 0) {
        month += 12;
        year -= 1
    }
    if (month > 11) {
        month -= 12;
        year += 1
    }
    if (year == yearMax - 1) yearMax += 1;
    if (year == yearMin) yearMin -= 1;
    check();
    var close_window = newElement('p', [
        ['id', 'close', 1]
    ], false, parentEle);

    var close_link = newElement('a', [
        ['href', 'javascript:removeCalender()', 1],
        ['className', 'close_window', 1]
    ], 'Fermer', close_window);
    var img_close = newElement('img', [
        ['src', 'img/close_small.gif', 1],
        ['id', 'img_close', 1]
    ], false, close_link);
    var control = newElement('p', [
        ['id', 'control', 1]
    ], false, parentEle);
    var controlPlus = newElement('a', [
        ['href', 'javascript:month=month-1;calender()', 1],
        ['className', 'controlPlus', 1]
    ], '<', control);
    var select = newElement('select', [
        ['onchange', function () {
            setMonth(this)
        }]
    ], false, control);
    for (var i = 0; i < allNameOfMonths.length; i++) newElement('option', [
        ['value', i, 1]
    ], allNameOfMonths[i], select);
    select.selectedIndex = month;
    select = newElement('select', [
        ['onchange', function () {
            setYear(this)
        }]
    ], false, control);
    for (var i = yearMin; i < yearMax; i++) newElement('option', [
        ['value', i, 1]
    ], i, select);
    select.selectedIndex = year - yearMin;
    controlPlus = newElement('a', [
        ['href', 'javascript:month++;calender()', 1],
        ['className', 'controlPlus', 1]
    ], '>', control);
    check();
    currentDay = 1 - new Date(year, month, 1).getDay();
    if (currentDay > 0) currentDay -= 7;
    currentDayZero = currentDay;
    var newMonth = newElement('table', [
        ['cellSpacing', 0, 1],
        ['onclick', function (e) {
            setValue(setTarget(e))
        }]
    ], false, parentEle);
    var newMonthBody = newElement('tbody', false, false, newMonth);
    var tr = newElement('tr', [
        ['className', 'head', 1]
    ], false, newMonthBody);
    tr = newElement('tr', [
        ['className', 'weekdays', 1]
    ], false, newMonthBody);
    for (i = 0; i < 7; i++) td = newElement('td', false, allNameOfWeekDays[i], tr);
    tr = newElement('tr', [
        ['className', 'week', 1]
    ], false, newMonthBody);
    for (i = 0; i < allMonth[month] - currentDayZero; i++) {
        var currentClass = false;
        currentDay++;
        if (currentDay == day && month == monthZero && year == yearZero) addClass('today');
        if (currentDay <= 0) {
            if (currentDayZero != -7) td = newElement('td', false, false, tr);
        } else {
            if ((currentDay - currentDayZero) % 7 == 0) addClass('holiday');
            td = newElement('td', (!currentClass ? false : [
                ['className', currentClass, 1]
            ]), currentDay, tr);
            if ((currentDay - currentDayZero) % 7 == 0) tr = newElement('tr', [
                ['className', 'week', 1]
            ], false, newMonthBody);
        }
        if (i == allMonth[month] - currentDayZero - 1) {
            i++;
            while (i % 7 != 0) {
                i++;
                td = newElement('td', false, false, tr)
            };
        }
    }

}

function showCalender(ele) {
    if ($j('#basis')[0]) {
        removeCalender()
    } else {
        target = $(ele.id.replace(/for_/, ''));
        var basis = ele.parentNode.insertBefore(document.createElement('div'), ele);
        basis.id = 'basis';
        newElement('div', [
            ['id', 'calender', 1]
        ], false, basis);
        calender();
    }
}


if (!window.Node) {
    var Node = {
        ELEMENT_NODE: 1,
        TEXT_NODE: 3
    };
}

function checkNode(node, filter) {
    return (filter == null || node.nodeType == Node[filter] || node.nodeName.toUpperCase() == filter.toUpperCase());
}

function getChildren(node, filter) {
    var result = new Array();
    if (node != null) {
        var children = node.childNodes;
        for (var i = 0; i < children.length; i++) {
            if (checkNode(children[i], filter)) result[result.length] = children[i];
        }
    }
    return result;
}

function getChildrenByElement(node) {
    return getChildren(node, "ELEMENT_NODE");
}

function getFirstChild(node, filter) {
    var child;
    var children = node.childNodes;
    for (var i = 0; i < children.length; i++) {
        child = children[i];
        if (checkNode(child, filter)) return child;
    }
    return null;
}

function getFirstChildByText(node) {
    return getFirstChild(node, "TEXT_NODE");
}

function getNextSibling(node, filter) {
    for (var sibling = node.nextSibling; sibling != null; sibling = sibling.nextSibling) {
        if (checkNode(sibling, filter)) return sibling;
    }
    return null;
}

function getNextSiblingByElement(node) {
    return getNextSibling(node, "ELEMENT_NODE");
}
/****************************************/


/********** Menu Functions & Properties   ******************/

var activeMenu = null;

function showMenu() {
    if (activeMenu) {
        activeMenu.className = "";
        getNextSiblingByElement(activeMenu).style.display = "none";
    }
    if (this == activeMenu) {
        activeMenu = null;
    } else {
        this.className = "on";
        getNextSiblingByElement(this).style.display = "block";
        activeMenu = this;
    }
    return false;
}

function initMenu() {
    var menus, menu, text, aRef, i;
    if ($("menu")) {
        menus = getChildrenByElement($("menu"));
        for (i = 0; i < menus.length; i++) {
            menu = menus[i];
            text = getFirstChildByText(menu);
            aRef = document.createElement("a");
            if (aRef == null) {
                menu.replaceChild(aRef, text);
                aRef.appendChild(text);
                aRef.href = "#";
                aRef.onclick = showMenu;
                aRef.onfocus = function () {
                    this.blur()
                };
            }
        }
    }
}

if (document.createElement) window.onload = initMenu;

/************** fonction pour afficher/cacher le menu     ***********/

function ShowHideMenu(menu, onouoff) {
    if (typeof ($) == 'function') {
        monmenu = $j("#" + menu)[0];
        mondivmenu = $j("#menu")[0];
        monadmin = $j("#admin")[0];
        monhelp = $j("#aide")[0];
    } else if (document.all) {
        monmenu = document.all[menu];
        mondivmenu = document.all["menu"];
        monadmin = document.all["admin"];
        monhelp = document.all["aide"];
    } else return;

    if (menu == "ssnav") {
        if (onouoff == "fermee") {
            monmenu.style.display = "block";
            monadmin.className = "on";
        } else if (onouoff == "ouverte") {
            monmenu.style.display = "none";
            monadmin.className = "off";
        }
    } else if (menu == "ssnavaide") {
        if (onouoff == "fermee") {
            monmenu.style.display = "block";
            monhelp.className = "on";
        } else if (onouoff == "ouverte") {
            monmenu.style.display = "none";
            monhelp.className = "off";
        }
    } else {
        if (onouoff == "on") {
            monmenu.style.display = "block";
            mondivmenu.className = "on";
        } else if (onouoff == "off") {
            monmenu.style.display = "none";
            mondivmenu.className = "off";
        }
    }
}


/****************************************/


function ouvreFenetre(page, largeur, hauteur) {
    window.open(page, "", "scrollbars=yes,menubar=no,toolbar=no,resizable=yes,width=" +
        largeur + ",height=" + hauteur);
}

/************** Fonction utilisées pour la gestion des listes multiples  ***********/

/**
 * Move item(s) from a multiple list to another
 *
 * @param  list1 Select Object Source list
 * @param  list2 Select Object Destination list
 */
function Move(list1, list2) {
    for (i = 0; i < list1.length; i++) {
        if (list1[i].selected) {
            o = new Option(list1.options[list1.options.selectedIndex].text, list1.options[list1.options.selectedIndex].value, false, true);
            list2.options[list2.options.length] = o;
            list1.options[list1.options.selectedIndex] = null;
            i--;
        }
    }
}

/**
 * Move an item from a multiple list to another
 *
 * @param  list1 Select Object Source list
 * @param  list2 Select Object Destination list
 */
function moveclick(list1, list2) {
    o = new Option(list1.options[list1.options.selectedIndex].text, list1.options[list1.options.selectedIndex].value, false, true);
    list2.options[list2.options.length] = o;
    list1.options[list1.options.selectedIndex] = null;
}

/**
 * Select all items from a multiple list
 *
 * @param  list Select Object Source list
 */
function selectall(list) {
    for (i = 0; i < list.length; i++) {
        list[i].selected = true;
    }
}

/**
 * Move an item from a multiple list to another
 *
 * @param  list1 Select identifier of the Source list
 * @param  list2 Select identifier of the Destination list
 */
function moveclick_ext(id_list1, id_list2) {
    var list1 = $(id_list1);
    var list2 = $(id_list2);

    if (list1.options.length == 1 && list1.options[0].value == '') {
        return;
    }
    moveclick(list1, list2);
}

/**
 * Select all items from a multiple list
 *
 * @param  list Select identifier of the Source list
 */
function selectall_ext(id_list) {
    var list = $(id_list);
    selectall(list);
}

/**
 * Move item(s) from a multiple list to another
 *
 * @param  list1 Select identifier of the Source list
 * @param  list2 Select identifier of the Destination list
 */
function Move_ext(id_list1, id_list2) {
    var list1 = $(id_list1);
    var list2 = $(id_list2);

    if (list1.options.length == 1 && list1.options[0].value == '') {
        return;
    }
    Move(list1, list2);
}
/*********************************************************/


var BrowserDetect = {
    init: function () {
        this.browser = this.searchString(this.dataBrowser) || "An unknown browser";
        this.version = this.searchVersion(navigator.userAgent) ||
            this.searchVersion(navigator.appVersion) ||
            "an unknown version";
        this.OS = this.searchString(this.dataOS) || "an unknown OS";
    },
    searchString: function (data) {
        for (var i = 0; i < data.length; i++) {
            var dataString = data[i].string;
            var dataProp = data[i].prop;
            this.versionSearchString = data[i].versionSearch || data[i].identity;
            if (dataString) {
                if (dataString.indexOf(data[i].subString) != -1)
                    return data[i].identity;
            } else if (dataProp)
                return data[i].identity;
        }
    },
    searchVersion: function (dataString) {
        var index = dataString.indexOf(this.versionSearchString);
        if (index == -1) return;
        return parseFloat(dataString.substring(index + this.versionSearchString.length + 1));
    },
    dataBrowser: [{
            string: navigator.userAgent,
            subString: "Chrome",
            identity: "Chrome"
        },
        {
            string: navigator.userAgent,
            subString: "OmniWeb",
            versionSearch: "OmniWeb/",
            identity: "OmniWeb"
        },
        {
            string: navigator.vendor,
            subString: "Apple",
            identity: "Safari",
            versionSearch: "Version"
        },
        {
            prop: window.opera,
            identity: "Opera"
        },
        {
            string: navigator.vendor,
            subString: "iCab",
            identity: "iCab"
        },
        {
            string: navigator.vendor,
            subString: "KDE",
            identity: "Konqueror"
        },
        {
            string: navigator.userAgent,
            subString: "Firefox",
            identity: "Firefox"
        },
        {
            string: navigator.vendor,
            subString: "Camino",
            identity: "Camino"
        },
        { // for newer Netscapes (6+)
            string: navigator.userAgent,
            subString: "Netscape",
            identity: "Netscape"
        },
        {
            string: navigator.userAgent,
            subString: "MSIE",
            identity: "Explorer",
            versionSearch: "MSIE"
        },
        {
            string: navigator.userAgent,
            subString: "Gecko",
            identity: "Mozilla",
            versionSearch: "rv"
        },
        { // for older Netscapes (4-)
            string: navigator.userAgent,
            subString: "Mozilla",
            identity: "Netscape",
            versionSearch: "Mozilla"
        }
    ],
    dataOS: [{
            string: navigator.platform,
            subString: "Win",
            identity: "Windows"
        },
        {
            string: navigator.platform,
            subString: "Mac",
            identity: "Mac"
        },
        {
            string: navigator.userAgent,
            subString: "iPhone",
            identity: "iPhone/iPod"
        },
        {
            string: navigator.platform,
            subString: "Linux",
            identity: "Linux"
        }
    ]

};

BrowserDetect.init();

function resize_frame_contact(mode) {
    if (parent.$('contact_iframe_attach') && mode == 'contact') {
        var contentHeight = 370;
        parent.$('contact_iframe_attach').style.height = contentHeight + "px";
    } else if (parent.$('info_contact_iframe_attach') && mode != 'contact') {
        var contentHeight = Math.round($j('#inner_content_contact').height()) + 100;
        parent.$('info_contact_iframe_attach').style.height = contentHeight + "px";
    } else {
        var contentHeight = Math.round($j('#inner_content_contact').height()) + 100;
        parent.$('iframe_tab').style.height = contentHeight + "px";
    }
}

/**
 * Resize frames in a modal
 *
 * @param  id_modal String Modal identifier
 * @param  id_frame String Frame identifier of the frame to resize
 * @param  resize_width Integer New width
 * @param  resize_height Integer New Height
 */
function resize_frame_view(id_modal, id_frame, resize_width, resize_height) {
    var modal = $(id_modal);
    if (modal) {
        if ($('divList')) {
            $('divList').style.display = 'none';
        }
        var newwidth = modal.getWidth();
        var newheight = modal.getHeight() - 30;
        var frame2 = $(id_frame);
        var windowSize = new Array();
        if (frame2 != null) {
            windowSize = getWindowSize();
            newwidth = windowSize[0] - 40;
            frame2.style.width = newwidth + "px";
        }
        if (resize_height == true && frame2 != null) {
            newheight = windowSize[1] - 90;
            frame2.style.height = newheight + "px";
        }
    }
}

function getWindowSize() {
    if (window.innerWidth || window.innerHeight) {
        var width = window.innerWidth;
        var height = window.innerHeight;
    } else {
        var width = $(document.documentElement).getWidth();
        var height = $(document.documentElement).getHeight();
    }
    return [width, height];
}

/*************** Tabs functions *****************/

function opentab(eleframe, url) {
    var eleframe1 = $(eleframe);
    eleframe1.src = url;
}

/********************************/

/*************** Modal functions *****************/

function displayModal(url, id_mod, height, width, mode_frm) {
    new Ajax.Request(url, {
        method: 'post',
        parameters: {},
        onSuccess: function (answer) {
            createModal(answer.responseText, id_mod, height, width, mode_frm);
        },
        onFailure: function () {}
    });
}

/**
 * Create a modal window
 *
 * @param txt String Text of the modal (innerHTML)
 * @param id_mod String Modal identifier
 * @param height String Modal Height in px
 * @param width String Modal width in px
 * @param mode_frm String Modal mode : fullscreen or ''
 * @param iframe_container_id Iframe container if function is called in a frame : id_frame or ''
 */
function createModal(txt, id_mod, height, width, mode_frm, iframe_container_id) {
    if (height == undefined || height == '') {
        height = '100px';
    }
    if (width == undefined || width == '') {
        width = '400px';
    }
    if (iframe_container_id == undefined || iframe_container_id == '') {
        iframe_container_id = '';
    }
    if (mode_frm == 'fullscreen') {
        width = (screen.availWidth) + 'px';
        height = (screen.availHeight) + 'px';
    }

    if (id_mod && id_mod != '') {
        id_layer = id_mod + '_layer';
    } else {
        id_mod = 'modal';
        id_layer = 'lb1-layer';
    }
    var tmp_width = width;
    var tmp_height = height;

    //aor : ne prend pas toute la hauteur de la fenetre (certain bouton peuvent etre accessible!)
    //var layer_height = $('container').clientHeight;
    var layer_height = document.body.clientHeight;

    //lgi : à quoi cela sert ?
    /*if(layer_height < $('container').scrollHeight)
    {
        layer_height = 5 * layer_height;
    }
    else if(layer_height = $('container').scrollHeight)
    {
        layer_height = 2 * layer_height;
    }*/
    //lgi
    var layer_width = document.getElementsByTagName('html')[0].offsetWidth - 5;
    var layer = new Element('div', {
        'id': id_layer,
        'class': 'lb1-layer',
        'style': "display:block;filter:alpha(opacity=70);opacity:.70;z-index:" + get_z_indexes()['layer'] + ';width :' + (layer_width) + "px;height:" + layer_height + 'px;'
    });


    if (mode_frm == 'fullscreen') {
        var fenetre = new Element('div', {
            'id': id_mod,
            'class': 'modal',
            'style': 'top:0px;left:0px;width:' + width + ';height:' + height + ";z-index:" + get_z_indexes()['modal'] + ";position:absolute;"
        });
    } else {
        var fenetre = new Element('div', {
            'id': id_mod,
            'class': 'modal',
            'style': 'top:0px;left:0px;' + 'width:' + width + ';height:' + height + ";z-index:" + get_z_indexes()['modal'] + ";margin-top:0px;margin-left:0px;position:absolute;"
        });
    }

    if (iframe_container_id != '') {
        var iframe_container = document.getElementById(iframe_container_id);
        Element.insert(iframe_container.contentWindow.document.body, layer);
        Element.insert(iframe_container.contentWindow.document.body, fenetre);
    } else {

        Element.insert(document.body, layer);
        Element.insert(document.body, fenetre);
    }

    if (mode_frm == 'fullscreen') {
        navName = BrowserDetect.browser;
        if (navName == 'Explorer') {
            if (width == '1080px') {
                fenetre.style.width = (document.getElementsByTagName('html')[0].offsetWidth - 55) + "px";
            }
        } else {
            fenetre.style.width = (document.getElementsByTagName('html')[0].offsetWidth - 30) + "px";
        }
        fenetre.style.height = (document.getElementsByTagName('body')[0].offsetHeight - 20) + "px";
    }

    Element.update(fenetre, txt);
    Event.observe(layer, 'mousewheel', function (event) {
        Event.stop(event);
    }.bindAsEventListener(), true);
    Event.observe(layer, 'DOMMouseScroll', function (event) {
        Event.stop(event);
    }.bindAsEventListener(), false);
    $(id_mod).focus();
    $j("input[type='button']").prop("disabled", false).css("opacity", "1");
}

/**
 * Destroy a modal window
 *
 * @param id_mod String Modal identifier
 */
function destroyModal(id_mod) {
    if ($j('#divList')) {
        $j('#divList').css("display", "block");
    }
    if (id_mod == undefined || id_mod == '') {
        id_mod = 'modal';
        id_layer = 'lb1-layer';
    } else {
        id_layer = id_mod + '_layer';
    }
    if (isAlreadyClick) {
        isAlreadyClick = false;
    }
    document.getElementsByTagName('body')[0].removeChild($j("#" + id_mod)[0]);
    document.getElementsByTagName('body')[0].removeChild($j("#" + id_layer)[0]);
    $j("input[type='button']").prop("disabled", false).css("opacity", "1");
    $j('body', window.top.window.document).css("overflow", "auto");

    // FIX IE 11
    if ($j('#leftPanelShowDocumentIframe')) {
        $j('#leftPanelShowDocumentIframe').show();
    }
    if ($j('#rightPanelShowDocumentIframe')) {
        $j('#rightPanelShowDocumentIframe').show();
    }
}

/**
 * Calculs the z indexes for a modal
 *
 * @return array The z indexes of the layer and the modal
 */
function get_z_indexes() {

    var elem = document.getElementsByClassName('modal');
    if (elem == undefined || elem == NaN) {
        return {
            layer: 995,
            modal: 1000
        };
    } else {
        var max_modal = 1000;
        for (var i = 0; i < elem.length; i++) {
            if (elem[i].style.zIndex >= max_modal) {
                max_modal = elem[i].style.zIndex;
            }
        }
        max_layer = max_modal + 5;
        max_modal = max_modal + 10;

        return {
            layer: max_layer,
            modal: max_modal
        };
    }
}

/***********************************************************************/

/*************** Actions management functions and vars *****************/

/**
 * Pile of the actions to be executed
 * Object
 */
var pile_actions = {
    values: [],
    action_push: function (val) {
        this.values.push(val);
    },
    action_pop: function () {
        return this.values.pop();
    }
};
var res_ids = '';
var do_nothing = false;

var actions_status = {
    values: [],
    action_push: function (val) {
        this.values.push(val);
    },
    action_pop: function () {
        return this.values.pop();
    }
};
/**
 * Executes the last actions in the actions pile
 *
 */
function end_actions() {
    var req_action = pile_actions.action_pop();
    if (req_action) {
        if (req_action.match('to_define')) {
            req_action = req_action.replace('to_define', res_ids);
            do_nothing = true;
        }
        try {
            eval(req_action);
        } catch (e) {
            alert('Error during pop action : ' + req_action);
        }
    }

}

/**
 * If the action has open a modal, destroy the action modal, and if this is the last action of the pile, reload the opener window
 *
 */
function close_action(id_action, page, path_manage_script, mode_req, res_id_values, tablename, id_coll) {
    var modal = $('modal_' + id_action);
    if (modal) {
        destroyModal('modal_' + id_action);
    }
    if (pile_actions.values.length == 0) {
        if (actions_status.values.length > 0) {
            var status = actions_status.values[actions_status.values.length - 1];
            action_done = action_change_status(path_manage_script, mode_req, res_id_values, tablename, id_coll, status, page);
        } else {
            if (page != '' && page != NaN && page && page != null) {
                if (typeof window['angularSignatureBookComponent'] != "undefined") {
                    window.angularSignatureBookComponent.componentAfterAction();
                } else {
                    do_nothing = false;
                    window.top.location.href = page;
                }

            } else if (do_nothing == false) {
                if (typeof window['angularSignatureBookComponent'] != "undefined") {
                    window.angularSignatureBookComponent.componentAfterAction();
                } else {
                    window.top.location.hash = "";
                    window.top.location.reload();
                }


            }
            do_nothing = false;
        }

    }
}

/**
 * Validates the form of an action
 *
 * @param current_form_id String  Identifier of the form to validate
 * @param path_manage_script String  Path to the php script called in the Ajax object to validates the form
 * @param id_action String  Action identifier
 * @param values String  Action do something on theses items  listed in this string
 * @param table String  Table used for the action
 * @param module String  Action is this module
 * @param coll_id String  Collection identifier
 * @param mode String Action mode : mass or page
 * @param protect_string bool
 */
function valid_action_form(current_form_id, path_manage_script, id_action, values, table, module, coll_id, mode, protect_string, advancedMode) {
    var frm_values;
    var chosen_action_id;

    if (typeof advancedMode !== "undefined") {
        frm_values = "so#use#less"; // Sert juste a remplir frm_values pour manage_actions
        chosen_action_id = advancedMode[0];
    } else {
        frm_values = get_form_values(current_form_id);
        chosen_action_id = get_chosen_action(current_form_id);
    }

    if (protect_string != false) {
        frm_values = frm_values.replace(/\'/g, "\\'");
        frm_values = frm_values.replace(/\"/g, '\\"');
        frm_values = frm_values.replace(/\r\n/g, ' ');
        frm_values = frm_values.replace(/\r/g, ' ');
        frm_values = frm_values.replace(/\n/g, ' ');
    }

    if (values && table && module && coll_id && chosen_action_id != '') {

        new Ajax.Request(path_manage_script, {
            method: 'post',
            parameters: {
                action_id: id_action,
                form_to_check: current_form_id,
                req: 'valid_form',
                form_values: frm_values
            },
            onCreate: function (answer) {
                //show loading image in toolbar
                $j("input[type='button']").prop("disabled", true).css("opacity", "0.5");
            },
            onSuccess: function (answer) {
                eval('response=' + answer.responseText);
                if (response.status == 0) { //form values checked
                    var hist = '';
                    if (chosen_action_id == 'end_action') {
                        hist = 'Y';
                    } else {
                        hist = 'N';
                    }

                    if (response.manage_form_now == false) {
                        pile_actions.action_push("action_send_form_confirm_result( '" + path_manage_script + "', '" + mode + "', '" + id_action + "', '" + values + "','" + table + "', '" + module + "','" + coll_id + "',  '" + frm_values + "',  '" + hist + "');");
                        if (chosen_action_id == 'end_action') {
                            end_actions();
                        } else {
                            action_send_first_request(path_manage_script, mode, chosen_action_id, values, table, module, coll_id);
                        }
                    } else {
                        if (chosen_action_id != 'end_action') {
                            pile_actions.action_push("action_send_first_request( '" + path_manage_script + "', '" + mode + "', '" + chosen_action_id + "', 'to_define','" + table + "', '" + module + "','" + coll_id + "');");
                        }
                        action_send_form_confirm_result(path_manage_script, mode, id_action, values, table, module, coll_id, frm_values, hist);
                    }

                } else { //  Form Params errors
                    try {
                        $('frm_error_' + id_action).innerHTML = response.error_txt;
                        alert($('frm_error_' + id_action).innerHTML);
                        $j("input[type='button']").prop("disabled", false).css("opacity", "1");

                    } catch (e) {

                    }
                }
            },
            onFailure: function (error) {
                console.log(error);
            }
        });

    } else if (chosen_action_id == '') {
        alert('Aucune action choisie');
    } else {
        console.log('Action Error!');
    }
}

/**
 * Get the chosen action identifier in the form
 *
 * @param form_id String  Identifier of the form
 */
function get_chosen_action(form_id) {
    var frm = $(form_id);
    for (var i = 0; i < frm.elements.length; i++) {
        if (frm.elements[i].id == 'chosen_action') {
            if (frm.elements[i].tagName == 'INPUT') {
                return frm.elements[i].value;
            } else if (frm.elements[i].tagName == 'SELECT') {
                return frm.elements[i].options[frm.elements[i].selectedIndex].value;
            } else {
                break;
            }
        }
    }
    return '';
}

/**
 * Get the values of the form in an string (Id_field1#field_value1$$Id_field2#field_value2$$)
 *
 * @param form_id String  Identifier of the form
 * @return String  Values of the form
 */
function get_form_values(form_id, return_string, include_buttons) {
    if (typeof (return_string) == "undefined" || return_string === null) {
        var in_string = true;
    } else {
        var in_string = return_string;
    }

    if (typeof (include_buttons) == "undefined" || include_buttons === null) {
        var get_buttons = true;
    } else {
        var get_buttons = include_buttons;
    }
    var frm = $(form_id);

    if (in_string == true) {
        var val = '';
    } else {
        var val = {};
    }
    if (frm) {
        for (var i = 0; i < frm.elements.length; i++) {
            if (frm.elements[i].tagName == 'INPUT' || frm.elements[i].tagName == 'TEXTAREA') {
                if ((frm.elements[i].tagName == 'INPUT' && frm.elements[i].type != 'checkbox' && frm.elements[i].type != 'radio') || frm.elements[i].tagName == 'TEXTAREA') {
                    if ((frm.elements[i].tagName == 'INPUT' && (get_buttons == true || (get_buttons == false && frm.elements[i].type == 'text'))) || frm.elements[i].tagName == 'TEXTAREA') {
                        if (in_string == true) {
                            val += frm.elements[i].id + '#' + frm.elements[i].value + '$$';
                        } else {
                            val[frm.elements[i].id] = frm.elements[i].value;
                        }
                    }
                } else {
                    if (frm.elements[i].checked == true) {
                        if (in_string == true) {
                            val += frm.elements[i].id + '#' + frm.elements[i].value + '$$';
                        } else {
                            val[frm.elements[i].id] = frm.elements[i].value;
                        }
                    }
                }
            } else if (frm.elements[i].tagName == 'SELECT') {
                if (frm.elements[i].type != 'select-multiple') {
                    if (in_string == true) {
                        val += frm.elements[i].id + '#' + frm.elements[i].options[frm.elements[i].selectedIndex].value + '$$';
                    } else {
                        val[frm.elements[i].id] = frm.elements[i].options[frm.elements[i].selectedIndex].value;
                    }
                } else {
                    if (in_string == true) {
                        if (frm.elements[i].selectedOptions) {
                            val += frm.elements[i].id + '#';
                            for (var mult = 0; mult < frm.elements[i].selectedOptions.length; mult++) {
                                if (mult == 0) {
                                    val += frm.elements[i].selectedOptions[mult].value;
                                } else {
                                    val += '__' + frm.elements[i].selectedOptions[mult].value;
                                }

                            }
                            val += '$$';
                        }

                    } else {
                        var val_s;
                        for (var mult = 0; mult < frm.elements[i].selectedOptions.length; mult++) {
                            if (mult == 0) {
                                val_s += frm.elements[i].selectedOptions[mult].value;
                            } else {
                                val_s += '__' + frm.elements[i].selectedOptions[mult].value;
                            }

                        }
                        val[frm.elements[i].id] = val_s;
                    }
                }
            }
        }
        if (in_string == true) {
            val.substring(0, val.length - 3);
        }
    }
    return val;
}

/**
 * Sends the first ajax request to create a form or resolve a simple action
 *
 * @param path_manage_script String  Path to the php script called in the Ajax object
 * @param mode_req String Action mode : mass or page
 * @param id_action String  Action identifier
 * @param res_id_values String  Action do something on theses items listed in this string
 * @param tablename String  Table used for the action
 * @param modulename String  Action is this module
 * @param id_coll String  Collection identifier
 */
function action_send_first_request(path_manage_script, mode_req, id_action, res_id_values, tablename, modulename, id_coll) {
    if (id_action == undefined || id_action == null || id_action == '') {
        window.top.$('main_error').innerHTML = arr_msg_error['choose_action'];
    }
    if (res_id_values == undefined || res_id_values == null || res_id_values == '') {
        window.top.$('main_error').innerHTML += '<br/>' + arr_msg_error['choose_one_doc'];
    }

    if (res_id_values != '' && id_action != '' && tablename != '' && modulename != '' && id_coll != '' && (mode_req == 'page' || mode_req == 'mass')) {

        $j.ajax({
            url: path_manage_script,
            //dataType: "json",
            type: 'POST',
            data: {
                values: res_id_values,
                action_id: id_action,
                mode: mode_req,
                req: 'first_request',
                table: tablename,
                coll_id: id_coll,
                module: modulename
            },
            beforeSend: function () {
                //show loading image in toolbar
                $j("input[type='button']").prop("disabled", true).css("opacity", "0.5");

            },
            success: function (answer) {
                eval("response = " + answer);

                if (response.status == 0) {
                    var page_result = response.page_result;

                    if (response.action_status != '' && response.action_status != 'NONE') {
                        actions_status.action_push(response.action_status);
                    }
                    end_actions();
                    if (response.newResultId != '') {
                        res_id_values = response.newResultId;
                    }
                    close_action(id_action, page_result, path_manage_script, mode_req, res_id_values, tablename, id_coll);

                } else if (response.status == 2) { // Confirm asked to the user
                    var modal_txt = '<div class=h2_title>' + response.confirm_content + '</div>';
                    modal_txt += '<p class="buttons">';
                    modal_txt += '<input type="button" name="submit" id="submit" value="' + response.validate + '" class="button" onclick="if(response.action_status != \'\' && response.action_status != \'NONE\'){actions_status.action_push(response.action_status);}action_send_form_confirm_result( \'' + path_manage_script + '\', \'' + mode_req + '\',\'' + id_action + '\', \'' + res_id_values + '\', \'' + tablename + '\', \'' + modulename + '\', \'' + id_coll + '\', \'\', \'Y\');"/>';
                    modal_txt += ' <input type="button" name="cancel" id="cancel" value="' + response.cancel + '" class="button" onclick="pile_actions.action_pop();destroyModal(\'modal_' + id_action + '\');"/></p>';
                    window.top.createModal(modal_txt, 'modal_' + id_action, '150px', '300px');
                } else if (response.status == 3) { // Form to fill by the user
                    if (response.action_status != '' && response.action_status != 'NONE') {
                        actions_status.action_push(response.action_status);
                    }
                    window.top.createModal(response.form_content, 'modal_' + id_action, response.height, response.width, response.mode_frm);
                } else if (response.status == 4) { // Confirm asked to the user (for visa)
                    var modal_txt = '<div class=h2_title>' + response.error + '</div>';
                    modal_txt += '<p class="buttons">';
                    modal_txt += '<input type="button" name="submit" id="submit" value="' + response.validate + '" class="button" onclick="destroyModal(\'modal_' + id_action + '\')"/>';
                    window.top.createModal(modal_txt, 'modal_' + id_action, '100px', '300px');
                } else { // Param errors
                    if (console) {
                        console.log('param error');
                    } else {
                        alert('param error');
                    }
                    //close_action(id_action,  page_result);
                }

                /*$('send_mass').disabled = false;
                $('send_mass').style.opacity = "1";
                $('send_mass').value = "Valider";*/
            },
            error: function () {
                //alert('erreur');
            }
        });
    }
}

/**
 * Sends the second ajax request to process a form
 *
 * @param path_manage_script String  Path to the php script called in the Ajax object
 * @param mode_req String Action mode : mass or page
 * @param id_action String  Action identifier
 * @param res_id_values String  Action do something on theses items listed in this string
 * @param tablename String  Table used for the action
 * @param modulename String  Action is this module
 * @param id_coll String  Collection identifier
 * @param values_new_form String  Values of the form to process
 */
function action_send_form_confirm_result(path_manage_script, mode_req, id_action, res_id_values, tablename, modulename, id_coll, values_new_form, hist) {
    if (res_id_values != '' && (mode_req == 'mass' || mode_req == 'page') &&
        id_action != '' && tablename != '' &&
        modulename != '' && id_coll != '') {

        new Ajax.Request(path_manage_script, {
            method: 'post',
            parameters: {
                values: res_id_values,
                action_id: id_action,
                mode: mode_req,
                req: 'second_request',
                table: tablename,
                coll_id: id_coll,
                module: modulename,
                form_values: values_new_form,
                hist: hist
            },
            onCreate: function (answer) {
                //show loading image in toolbar
                $j("input[type='button']").prop("disabled", true).css("opacity", "0.5");
            },
            onSuccess: function (answer) {
                eval('response=' + answer.responseText);
                if (response.status == 0) //Form or confirm processed ok
                {
                    /*res_ids = response.result_id;
                    if(res_id_values == 'none' && res_ids != '')
                    {
                        res_id_values = res_ids;
                    }
                    end_actions();
                    var table_name = tablename;
                    if(response.table && response.table != '')
                    {
                        table_name = response.table;
                    }
                    var page_result = response.page_result;
                    page_result_final = response.page_result;
                    close_action(id_action, page_result, path_manage_script, mode_req, res_id_values, table_name, id_coll);*/
                    var modal = $('modal_' + id_action);
                    if (modal) {
                        destroyModal('modal_' + id_action);
                    }
                    if (pile_actions.values.length > 0) {
                        end_actions();
                    } else {
                        res_ids = response.result_id;
                        if (res_id_values == 'none' && res_ids != '') {
                            res_id_values = res_ids;
                        }
                        var table_name = tablename;
                        if (response.table && response.table != '') {
                            table_name = response.table;
                        }
                        var page_result = response.page_result;
                        page_result_final = response.page_result;
                        close_action(id_action, page_result, path_manage_script, mode_req, res_id_values, table_name, id_coll);
                    }
                } else //  Form Params errors
                {
                    try {
                        $('frm_error').innerHTML = response.error_txt;
                        $j("input[type='button']").prop("disabled", true).css("opacity", "0.5");
                    } catch (e) {}
                }
            },
            onFailure: function () {}
        });
    }
}

function action_change_status(path_manage_script, mode_req, res_id_values, tablename, id_coll, status, page) {
    if (res_id_values != '' && (mode_req == 'mass' || mode_req == 'page') && tablename != '' && id_coll != '') {

        $j.ajax({
            cache: false,
            url: path_manage_script,
            type: 'POST',
            data: {
                values: res_id_values,
                mode: mode_req,
                req: 'change_status',
                table: tablename,
                coll_id: id_coll,
                new_status: status
            },
            success: function (answer) {
                // setTimeout(function(){
                if (answer.status == 0) {
                    actions_status.values = [];
                    // Status changed
                } else {
                    try {
                        $('frm_error').innerHTML = answer.error_txt;
                    } catch (e) {}
                }
                if (page != '' && page != NaN && page && page != null) {
                    do_nothing = false;
                    window.top.location.href = page;

                } else if (do_nothing == false) {

                    var cur_url = window.top.location.href;
                    if (cur_url.indexOf("&directLinkToAction") != -1) {
                        if (typeof window['angularSignatureBookComponent'] != "undefined") {
                            window.angularSignatureBookComponent.componentAfterAction();
                        } else {
                            window.top.location = cur_url.replace("&directLinkToAction", "");
                        }
                    } else {
                        if (typeof window['angularSignatureBookComponent'] != "undefined") {
                            window.angularSignatureBookComponent.componentAfterAction();
                        } else {
                            
                            var arr = window.top.location.href.split('&');
                            arr.shift();

                            var urlV2Param = {
                                moduleId : '',
                                resId : '',
                                userId : '',
                                groupId : '',
                                basket_id : '',
                                basketId : '',
                                actionId : '',
                            }

                            arr.forEach(element => {
                                if (element == 'module=basket') {
                                    urlV2Param.moduleId = element.split('=')[1];
                                }
                                if (element.indexOf('baskets=') > -1 ) {
                                    urlV2Param.basket_id = element.split('=')[1];
                                }                                
                                if (element.indexOf('basketId=') > -1 ) {
                                    urlV2Param.basketId = element.split('=')[1];
                                }
                                if (element.indexOf('resId=') > -1 ) {
                                    urlV2Param.resId = element.split('=')[1];
                                }
                                if (element.indexOf('userId=') > -1 ) {
                                    urlV2Param.userId = element.split('=')[1];
                                }
                                if (element.indexOf('groupIdSer=') > -1 ) {
                                    urlV2Param.groupId = element.split('=')[1];
                                }
                                if (element.indexOf('defaultAction=') > -1 ) {
                                    urlV2Param.actionId = element.split('=')[1];
                                    urlV2Param.actionId = urlV2Param.actionId.split('#')[0];
                                }
                            });

                            if (urlV2Param.basket_id.length > 0 && urlV2Param.moduleId.length > 0 && urlV2Param.resId.length > 0 && urlV2Param.userId.length > 0 &&  urlV2Param.groupId.length > 0 && urlV2Param.basketId.length > 0 && urlV2Param.actionId.length > 0) {
                                triggerAngular('#/basketList/users/'+urlV2Param.userId+'/groups/'+urlV2Param.groupId+'/baskets/' + urlV2Param.basketId);
                            } else {
                                window.top.location.hash = "";
                                window.top.location.reload();
                            }
                            
                        }
                    }
                }

                // fix for Chrome and firefox
                if (page_result_final != '') {
                    window.top.location.href = page_result_final;
                }

                do_nothing = false;
                // }, 200);
            }
        });
    }
    return true;
}
/***********************************************************************/


/*************** Xml management functions : used with tiny_mce to load mapping_file *****************/

/**
 * Remove a node in a xml file
 *
 * @param node Node Object Node to remove
 */
function remove_tag(node) {
    if (!node.data.replace(/\s/g, ''))
        node.parentNode.removeChild(node);
}

/**
 * Resize the current window
 *
 * @param x Integer  X size
 * @param y Integer  Y size
 */
function resize(x, y) {
    parent.window.resizeTo(x, y);
}

/**
 * Sets the current window to fullscreen
 */
function fullscreen() {
    parent.window.moveTo(0, 0);
    resize(screen.width - 10, screen.height - 30);
}

/**
 * Displays in a string all items of an array + its methods
 *
 */
function print_r(x, max, sep, l) {

    l = l || 0;
    max = max || 10;
    sep = sep || ' ';

    if (l > max) {
        return "[WARNING: Too much recursion]\n";
    }

    var
        i,
        r = '',
        t = typeof x,
        tab = '';

    if (x === null) {
        r += "(null)\n";
    } else if (t == 'object') {

        l++;

        for (i = 0; i < l; i++) {
            tab += sep;
        }

        if (x && x.length) {
            t = 'array';
        }

        r += '(' + t + ") :\n";

        for (i in x) {
            try {
                r += tab + '[' + i + '] : ' + print_r(x[i], max, sep, (l + 1));
            } catch (e) {
                return "[ERROR: " + e + "]\n";
            }
        }

    } else {

        if (t == 'string') {
            if (x == '') {
                x = '(empty)';
            }
        }

        r += '(' + t + ') ' + x + "\n";

    }

    return r;

}

/**
 * Unlock a basket using Ajax
 *
 * @param path_script String Path to the Ajax script
 * @param id String Basket id to unlock
 * @param coll String Collection identifier of the basket
 **/
function unlock(path_script, id, coll) // A FAIRE
{
    if (path_script && res_id && coll_id) {
        new Ajax.Request(path_script, {
            method: 'post',
            parameters: {
                res_id: id,
                coll_id: coll
            },
            onSuccess: function (answer) {

                eval('response=' + answer.responseText);
                if (response.status != 0) {
                    if (console) {
                        console.log('Pb unlock');
                    } else {
                        alert('Pb unlock');
                    }
                }
            },
            onFailure: function () {}
        });
    }
}

function checkCommunication(contactId) {
    if (!contactId || !Number.isInteger(parseInt(contactId))) {
        Element.setStyle($('type_contact_communication_icon'), {
            visibility: 'hidden'
        });
        return false;
    }

    $j.ajax({
        url: '../../rest/contacts/' + contactId + '/communication',
        type: 'get',
        data: {},
        success: function (answer) {
            if (answer[0]) {
                Element.setStyle($('type_contact_communication_icon'), {
                    visibility: 'visible'
                });
            } else {
                Element.setStyle($('type_contact_communication_icon'), {
                    visibility: 'hidden'
                });
            }

        }
    });
}

function setContactType(mode, creation) {
    new Ajax.Request("index.php?dir=my_contacts&page=setContactType", {
        method: 'post',
        parameters: {
            contact_target: mode,
            can_add_contact: creation
        },
        onSuccess: function (answer) {
            $('contact_type').innerHTML = answer.responseText;
        }
    });
}

function checkContactType(mode, creation) {
    var bool = false;
    $j.ajax({
        url: 'index.php?dir=my_contacts&page=setContactType&display=false',
        type: 'POST',
        async: false,
        data: {
            contact_target: mode,
            can_add_contact: creation
        },
        success: function (answer) {
            if (answer.substring(0, 5) == 'false') {
                bool = false;
            } else {
                bool = true;
            }
        }
    });
    return bool;
}

/**
 * Show or hide the data related to a person in the contacts admin
 *
 * @param is_corporate Bool True the contact is corporate, Fasle otherwise
 **/
function show_admin_contacts(is_corporate, display) {
    var display_value = display || 'inline';
    var title = $j("#title_p");
    var lastname = $j("#lastname_p");
    var firstname = $j("#firstname_p");
    var function_p = $j("#function_p");
    var lastname_mandatory = $j("#lastname_mandatory");
    var society_mandatory = $j("#society_mandatory");
    if (is_corporate == true) {
        if (title) {
            title.css('display', 'none');
        }
        if (lastname) {
            lastname.css('display', 'none');
        }
        if (firstname) {
            firstname.css('display', 'none');
        }
        if (function_p) {
            function_p.css('display', 'none');
        }
        if (lastname_mandatory) {
            lastname_mandatory.css('display', 'none');
        }
        if (society_mandatory) {
            society_mandatory.css('display', 'none');
        }
    } else {
        if (title) {
            title.css('display', display_value);
        }
        if (lastname) {
            lastname.css('display', display_value);
        }
        if (firstname) {
            firstname.css('display', display_value);

        }
        if (function_p) {
            function_p.css('display', display_value);

        }
        if (lastname_mandatory) {
            lastname_mandatory.css('display', 'inline');

        }
        if (society_mandatory) {
            society_mandatory.css('display', 'none');
        }
    }
}
/**
 * Show or hide the data related to a person in the external contacts admin
 *
 * @param is_external Bool True the contact is external contact
 **/
function show_admin_external_contact(is_external, display) {
    var display_value = display || 'inline';
    var searchDirectory = $("search_directory");
    var externalContactLabel = $("m2m_id");
    if (is_external === false) {
        if (searchDirectory) {
            searchDirectory.style.display = "none";
        }
        if (externalContactLabel) {
            externalContactLabel.style.display = "none";
        }
    } else {
        if (searchDirectory) {
            searchDirectory.style.display = display_value;
        }
        if (externalContactLabel) {
            externalContactLabel.style.display = display_value;
        }
    }
}

/**
 * Returns in an array all values selected from check boxes or radio buttons
 *
 * @param name_input String Item Name
 * @return Array Checked values
 **/
function get_checked_values(name_input) {
    var arr = [];
    var items = document.getElementsByName(name_input);
    for (var i = 0; i < items.length; i++) {
        if (items[i].checked == true) {
            arr.push(items[i].value);
        }
    }
    return arr;
}

/**
 * Clears a form (empties it)
 *
 * @param form_id String Form identifier
 **/
function clear_form(form_id) {
    var frm = $(form_id);
    if (frm) {
        var items = frm.getElementsByTagName('INPUT');
        for (var i = 0; i < items.length; i++) {
            if (items[i].type == "text") {
                items[i].value = '';
            }
        }
        items = frm.getElementsByTagName('TEXTAREA');
        for (var i = 0; i < items.length; i++) {
            items[i].value = '';
        }
        items = frm.getElementsByTagName('SELECT');
        for (var i = 0; i < items.length; i++) {
            if (items[i].multiple == "true") {
                // TO DO
            } else {
                items[i].options[0].selected = 'selected';
            }
        }
    }
}

/*************** Apps Reports functions *****************/

/**
 * Function used to display the user access report
 *
 * @param url String Form Url of the php script which gets the results
 **/
function valid_userlogs(url) {
    var user_div = $('user_id');
    var user_id_val = '';
    if (user_div) {
        user_id_val = user_div.value;
    }

    if (url) {
        new Ajax.Request(url, {
            method: 'post',
            parameters: {
                user: user_id_val
            },
            onSuccess: function (answer) {
                var div_to_fill = $('result_userlogsstat');
                if (div_to_fill) {
                    div_to_fill.innerHTML = answer.responseText;
                }
            }
        });
    }
}

/**
 * Function used to display the letterbox reports
 *
 * @param url String Form Url of the php script which gets the results
 **/
function valid_report_by_period(url) {
    var type_period = '';
    var type_report = 'graph';
    var datestart = '';
    var dateend = '';
    var year = '';
    var month = '';

    if ($j('#entities_chosen').length) {
        var entities_chosen = [];
        $j("select#entities_chosen option:selected").each(function (key, entity) {
            entities_chosen.push(entity.value);
        });
        var entities_chosen_list = entities_chosen.join('#');

    }

    if ($j('#status_chosen').length) {
        var status_chosen = [];
        $j("select#status_chosen option:selected").each(function (key, status) {
            status_chosen.push(status.value);
        });
        var status_chosen_list = status_chosen.join('#');

    }

    if ($j('#priority_chosen').length) {
        var priority_chosen = [];
        $j("select#priority_chosen option:selected").each(function (key, priority) {
            priority_chosen.push(priority.value);
        });
        var priority_chosen_list = priority_chosen.join('#');

    }

    if ($j('#doctypes_chosen').length) {
        var doctypes_chosen = [];
        $j("select#doctypes_chosen option:selected").each(function (key, doctype) {
            doctypes_chosen.push(doctype.value);
        });
        var doctypes_chosen_list = doctypes_chosen.join('#');

    }


    var error = '';
    var report_id = '';
    //test =  'test';
    var report_id_item = $('id_report');
    if (report_id_item) {
        report_id = report_id_item.value;
    }

    var report = $('report_array');
    if (report && report.checked) {
        type_report = 'array';
    }
    var period_custom = $('custom_period');
    var period_year = $('period_by_year');
    var period_month = $('period_by_month');
    var sub_entities = '';
    if ($j('#sub_entities')[0]) {
        sub_entities = $j('#sub_entities')[0].checked;
    }
    if (period_custom && period_custom.checked) {
        type_period = 'custom_period';
        var datestart_item = $('datestart');
        if (datestart_item) {
            datestart = datestart_item.value;
        }
        var dateend_item = $('dateend');
        if (dateend_item) {
            dateend = dateend_item.value;
        }
    } else if (period_year && period_year.checked) {
        type_period = 'period_year';
        var years_list = $('the_year');
        if (years_list) {
            year = years_list.options[years_list.selectedIndex].value;
        }
    } else if (period_month && period_month.checked) {
        type_period = 'period_month';
        var months_list = $('the_month');
        if (months_list) {
            month = months_list.options[months_list.selectedIndex].value;
        }
    } else {
        error = 'empty_type_period';
    }

    if (type_period != '' && url && error == '') {
        new Ajax.Request(url, {
            method: 'post',
            parameters: {
                id_report: report_id,
                report_type: type_report,
                period_type: type_period,
                the_year: year,
                the_month: month,
                entities_chosen: entities_chosen_list,
                sub_entities: sub_entities,
                status_chosen: status_chosen_list,
                priority_chosen: priority_chosen_list,
                doctypes_chosen: doctypes_chosen_list,
                date_start: datestart,
                date_fin: dateend
            },
            onSuccess: function (answer) {
                var div_to_fill = $('result_period_report');

                if (div_to_fill) {

                    if (type_report == 'array') {
                        if (response.status == 2) {
                            eval("response = " + answer.responseText);

                            $('main_error_popup').innerHTML = response.error_txt;
                            $('main_error_popup').style.display = 'table-cell';
                            Element.hide.delay(3, 'main_error_popup');
                        } else {
                            div_to_fill.innerHTML = answer.responseText;
                            document.getElementById('src1').src = document.getElementById('src1').src + '?unique=' + new Date().valueOf();
                        }
                    } else if (type_report == 'graph') {
                        eval("response = " + answer.responseText);

                        if (response.status == 2) {
                            $('main_error_popup').innerHTML = response.error_txt;
                            $('main_error_popup').style.display = 'table-cell';
                            Element.hide.delay(3, 'main_error_popup');
                        } else {
                            div_to_fill.innerHTML = '<div style="text-align:center;"><b>' + response.title +
                                '</b><canvas id="src1" height="100px"></canvas>' +
                                '</div>';

                            var barChartData = {
                                labels: response.label,
                                datasets: [{
                                    fillColor: "rgba(151,187,205,0.5)",
                                    strokeColor: "rgba(151,187,205,0.8)",
                                    highlightFill: "rgba(151,187,205,0.75)",
                                    highlightStroke: "#F99830",
                                    data: response.data
                                }]

                            }

                            if (url.indexOf("entity_response_rate_stat") > 0) {
                                var optionBarChart = {
                                    responsive: true,
                                    scaleShowVerticalLines: false,
                                    scaleOverride: true,
                                    scaleSteps: 10,
                                    scaleStepWidth: 10,
                                    scaleStartValue: 0,
                                    animation: false,
                                }
                            } else {
                                var optionBarChart = {
                                    responsive: true,
                                    scaleShowVerticalLines: false,
                                    animation: false,
                                }
                            }

                            var ctx = document.getElementById("src1").getContext("2d");
                            window.myBar = new Chart(ctx).Bar(barChartData, optionBarChart);
                        }

                    }
                }
            },
            onCreate: function (answer) {
                $j('#validate').val('Traitement en cours ...');
                $j('#validate').prop('disabled', true);
                $j('#validate').css("opacity", "0.5");

            },
            onComplete: function (answer) {
                $j('#validate').val('Valider');
                $j('#validate').prop('disabled', false);
                $j('#validate').css("opacity", "1");

            }
        });
    }
}

/**
 * Launch the Ajax autocomplete object to activate autocompletion on a field
 *
 * @param path_script String Path to the Ajax script
 **/
function launch_autocompleter(path_script, id_text, id_div, minCharSearch) {
    var input = id_text;
    var div = id_div;
    var minCharSearch = minCharSearch || 2;
    if (path_script) {
        // Ajax autocompleter object creation
        new Ajax.Autocompleter(input, div, path_script, {
            method: 'get',
            paramName: 'Input',
            minChars: 2
        });
    } else {
        if (console != null) {
            console.log('error parameters launch_autocompleter function');
        } else {
            alert('error parameters launch_autocompleter function');
        }
    }
}

/**
 * Launch the Ajax autocomplete object to activate autocompletion on a field and then update an input field
 *
 * @param path_script String Path to the Ajax script
 **/
function launch_autocompleter_update(path_script, id_text, id_div, minCharSearch, updateElement) {
    var input = id_text || 'contact';
    var div = id_div || 'show_contacts';
    var minCharSearch = minCharSearch || 2;

    contact_autocompleter = new Ajax.Autocompleter(input, div, path_script, {
        method: 'get',
        paramName: 'what',
        minChars: minCharSearch,
        afterUpdateElement: function (text, li) {
            $(updateElement).value = li.id;
        }
    });
}

/**
 * Gets the indexes for a given collection and fills a div with it
 *
 * @param url String Url to the Ajax script
 * @param id_coll String Collection identifier
 **/
function get_opt_index(url, id_coll) {
    if (url && id_coll) {
        new Ajax.Request(url, {
            method: 'post',
            parameters: {
                coll_id: id_coll
            },
            onSuccess: function (answer) {
                var div_to_fill = $('opt_index');

                if (div_to_fill) {
                    div_to_fill.innerHTML = answer.responseText;
                }
            }
        });
    }
}

/**
 * Gets the indexes for a given document type (used in details page)
 *
 * @param doctype_id String Document type identifier
 * @param url String Url to the Ajax script
 * @param error_empty_type Message to displays if the type is empty
 **/
function change_doctype_details(doctype_id, url, error_empty_type, res_id) {
    if (doctype_id != null && doctype_id != '' && doctype_id != NaN) {
        new Ajax.Request(url, {
            method: 'post',
            parameters: {
                type_id: doctype_id,
                res_id: res_id
            },
            onSuccess: function (answer) {
                eval("response = " + answer.responseText);
                //  alert(answer.responseText);
                if (response.status == 0) {
                    var indexes = response.new_opt_indexes;
                    var div_indexes = $('opt_indexes_custom');
                    if (div_indexes) {
                        div_indexes.update(indexes);
                    }

                } else {
                    try {
                        //  $('main_error').innerHTML = response.error_txt;
                    } catch (e) {

                    }
                }
            }
        });
    } else {
        try {
            //$('main_error').innerHTML = error_empty_type;
        } catch (e) {}
    }
}

function updateContent(url, id_div_to_update, onComplete_callback) {
    new Ajax.Updater(id_div_to_update, url, {
        parameters: {},
        onComplete: function () {
            if (onComplete_callback) {
                eval(onComplete_callback);
            }
        }
    });
}

function showValuesList(listId, spanId) {
    if (window.document.getElementById(listId).style.display == 'none') {
        window.document.getElementById(listId).style.display = 'block';
        window.document.getElementById(spanId).style.display = 'none';
    } else {
        window.document.getElementById(listId).style.display = 'none';
        window.document.getElementById(spanId).style.display = 'block';
    }
}

function hideIndex(mode_hide, display_val) {
    var displayVal = $(display_val);
    if (mode_hide == true) {
        if (displayVal) {
            Element.setStyle(displayVal, {
                display: 'none'
            });
        }
    } else {
        if (displayVal) {
            Element.setStyle(displayVal, {
                display: 'block'
            });
        }
    }
}

function checkAll() {
    $$('input[type=checkbox]').without($('all')).each(
        function (e) {
            if(e.id != ''){ // No check subentities checkbox
                if (e.checked != true) {
                    stockCheckbox('index.php?display=true&dir=indexing_searching&page=multiLink', e.value);
                }
                e.checked = true;
            }
        }
    )
}


function unCheckAll() {
    $$('input[type=checkbox]').without($('all')).each(
        function (e) {
            e.checked = false;
            stockCheckbox('index.php?display=true&dir=indexing_searching&page=multiLink&uncheckAll', e.value);
        }
    )
}

function show_attach(state) {
    if (state == 'true') {
        $('attach_show').slideDown();
    } else {
        $('attach_show').setStyle({
            display: 'none'
        });
    }
}

function addLinks(path_manage_script, child, parent, action, tableHist) {
    //window.alert('child : '+child+', parent : '+parent+', action : '+action);
    var divName = 'loadLinks';

    if (child != '' && parent != '' && action != '') {
        new Ajax.Request(path_manage_script, {
            method: 'post',
            parameters: {
                res_id: parent,
                res_id_child: child,
                mode: action,
                tableHist: tableHist

            },
            onSuccess: function (answer) {

                eval("response = " + answer.responseText);
                if (response.status == 0 || response.status == 1) {
                    if (typeof window.parent['angularSignatureBookComponent'] != "undefined") {
                        window.parent.angularSignatureBookComponent.componentAfterLinks();
                    }
                    if (response.status == 0) {
                        $(divName).innerHTML = response.links;

                        eval(response.exec_js);

                    } else {
                        //
                    }
                } else {
                    try {
                        $(divName).innerHTML = response.error_txt;
                    } catch (e) {}
                }
            }
        });
    }
}

function stockCheckbox(url, value) {
    if (value != '') {
        new Ajax.Request(url, {
            method: 'post',
            parameters: {
                courrier_purpose: value
            },
            onSuccess: function (answer) {

                monTableauJS = JSON.parse(answer.responseText);


            }
        })
    }

}

function cleanSessionBasket(url, value) {
    //fait appel à l'ajax cleanSessionBasket du module basket pour vider la $_SESSION['basket_used']
    if (value != '') {
        new Ajax.Request(url,

            {
                method: 'post',
                parameters: {
                    courrier_purpose: value
                },
                onSuccess: function (answer) {
                    eval("response = " + answer.responseText);
                    $j('#select_basket').submit();
                    //monTableauJS =  JSON.parse(answer.responseText);


                }
            })
    };

}

function loadRepList(id, option) {
    if ($('repList_' + id).style.display != 'none') {
        new Effect.toggle('repList_' + id, 'appear', {
            delay: 0.2
        });
    } else {
        new Effect.toggle('repList_' + id, 'appear', {
            delay: 0.2
        });

        var path_manage_script = 'index.php?page=loadRepList&display=true';

        if (typeof option == 'undefined')
            option = '';

        new Ajax.Request(path_manage_script, {
            method: 'post',
            parameters: {
                res_id_master: id,
                option: option
            },
            onSuccess: function (answer) {
                eval("response = " + answer.responseText);
                $('divRepList_' + id).innerHTML = response.toShow;
            }
        });
    }

}

function previsualiseAdminRead(e, json) {
    if ($('identifierDetailFrame')) {
        if ($('identifierDetailFrame').value == json.identifierDetailFrame) {
            return;
        }
    }

    var DocRef;
    if (e) {
        mouseX = e.pageX;
        mouseY = e.pageY;
    } else {
        mouseX = event.clientX;
        mouseY = event.clientY;

        if (document.documentElement && document.documentElement.clientWidth) {
            DocRef = document.documentElement;
        } else {
            DocRef = document.body;
        }

        mouseX += DocRef.scrollLeft;
        mouseY += DocRef.scrollTop;
    }
    var topPosition = mouseY + 10;
    var leftPosition = mouseX - 200;

    var writeHTML = '<table cellpadding="2">';
    for (i in json) {
        if (i != 'identifierDetailFrame') {
            writeHTML += '<tr>';
            writeHTML += '<td>';
            writeHTML += i;
            writeHTML += '</td>';
            writeHTML += '<td>';
            writeHTML += ' : ';
            writeHTML += '</td>';
            writeHTML += '<td>';
            writeHTML += json[i];
            writeHTML += '</td>';
            writeHTML += '</tr>';
        } else {
            writeHTML += '<input type="hidden" id="identifierDetailFrame" value="' + json[i] + '" />';
        }
    }
    writeHTML += '</table>'
    $('return_previsualise').update(writeHTML);
    $('return_previsualise').innerHTML;

    var divWidth = $('return_previsualise').getWidth();
    if (divWidth > 0) {
        leftPosition = mouseX - (divWidth + 40);
    }
    if (leftPosition < 0) {
        leftPosition = -leftPosition;
    }
    var divHeight = $('return_previsualise').getHeight();
    if (divHeight > 0) {
        topPosition = mouseY - (divHeight - 2);
    }

    if (topPosition < 0) {
        topPosition = 10;
    }

    //var scrollY = (document.all ? document.scrollTop : window.pageYOffset);
    var scrollY = f_filterResults(
        window.pageYOffset ? window.pageYOffset : 0,
        document.documentElement ? document.documentElement.scrollTop : 0,
        document.body ? document.body.scrollTop : 0
    );

    if (topPosition < scrollY) {
        topPosition = scrollY + 10;
    }

    $('return_previsualise').style.top = topPosition + 'px';
    $('return_previsualise').style.left = leftPosition + 'px';

    $('return_previsualise').style.maxWidth = '600px';
    $('return_previsualise').style.maxHeight = '600px';
    $('return_previsualise').style.overflowY = 'scroll';
    $('return_previsualise').style.display = 'block';

}

function goTo(where) {
    window.location.href = where;
}

function f_filterResults(n_win, n_docel, n_body) {
    var n_result = n_win ? n_win : 0;
    if (n_docel && (!n_result || (n_result > n_docel)))
        n_result = n_docel;
    return n_body && (!n_result || (n_result > n_body)) ? n_body : n_result;
}

function loadDocList(id) {
    //new Effect.toggle('docList_'+id, 'appear' , {delay:0.2});

    if ($('docList_' + id).style.display == 'none') {
        $('docList_' + id).setStyle({
            display: 'table'
        });
    } else {
        $('docList_' + id).setStyle({
            display: 'none'
        });
    }
    var path_manage_script = 'index.php?admin=contacts&page=ajaxLoadDocList&display=true';

    new Ajax.Request(path_manage_script, {
        method: 'post',
        parameters: {
            contact_id: id
        },
        onSuccess: function (answer) {
            eval("response = " + answer.responseText);
            $('divDocList_' + id).innerHTML = response.toShow;
        }
    });
}

function deleteContact(id, replaced_contact_id, replaced_address_id) {
    //alert(id+' '+replaced_contact_id);
    var path_manage_script = 'index.php?admin=contacts&page=ajaxDeleteContact&display=true';

    new Ajax.Request(path_manage_script, {
        method: 'post',
        parameters: {
            contactId: id,
            replacedContactId: replaced_contact_id,
            replacedAddressId: replaced_address_id
        },
        onSuccess: function (answer) {
            eval("response = " + answer.responseText);
            //alert(response.status);
            if (response.status == 0) {
                new Effect.toggle('divDeleteContact_' + id, 'blind', {
                    delay: 0.2
                });
                new Effect.toggle('deleteContactDiv_' + id, 'blind', {
                    delay: 0.2
                });
                new Effect.toggle('divDocList_' + id, 'blind', {
                    delay: 0.2
                });
                new Effect.toggle('tr_' + id, 'blind', {
                    delay: 0.2
                });
            } else {
                alert('Choisissez une adresse et un contact d\'abord');
            }
        }
    });
}

/************************************ LISTS ****************************************/
var globalEval = function (script) {
    if (window.execScript) {
        return window.execScript(script);
    } else if (navigator.userAgent.indexOf('KHTML') != -1) { //safari, konqueror..
        var s = document.createElement('script');
        s.type = 'text/javascript';
        s.innerHTML = script;
        document.getElementsByTagName('head')[0].appendChild(s);
    } else {
        return window.eval(script);
    }
};

//
function evalMyScripts(targetId) {
    var myScripts = document.getElementById(targetId).getElementsByTagName('script');
    for (var i = 0; i < myScripts.length; i++) {
        // alert(myScripts[i].innerHTML);
        globalEval(myScripts[i].innerHTML);
    }
}

/*
 *  Converts \n newline chars into <br> chars s.t. they are visible
 *  inside HTML
 */
function convertToHTMLVisibleNewline(value) {
    if (value != null && value != "") {
        return value.replace(/\\n /g, "<br/>");
    } else {
        return value;
    }
}

/*
 *  Converts \\n chars into \n newline chars s.t. they are visible
 *  inside text edit boxes
 */
function convertToTextVisibleNewLine(value) {
    if (value != null && value != "") {
        return value.replace(/\\n /g, "\n");
    } else {
        return value;
    }
}

function loadList(path, inDiv, modeReturn, init) {

    // alert (modeReturn);
    if (typeof (inDiv) === 'undefined') {
        var div = 'divList';
    } else {
        var div = inDiv;
    }
    if (typeof (modeReturn) === 'undefined') {
        var modeReturn = false;
    }
    if (typeof (init) === 'undefined' && window.top.$('main_error') != null) {
        window.top.$('main_error').innerHTML = '';
        window.top.$('main_info').innerHTML = '';
    }
    path = path.replace('#', '%23');
    new Ajax.Request(path, {
        method: 'post',
        parameters: {
            url: path
        },
        onCreate: function (answer) {
            //show loading image in toolbar
            if (document.getElementById("loading")) {
                var loader = $j('<div style="position: absolute;margin-top: -15px;"><svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="24px" height="30px" viewBox="0 0 24 30" style="enable-background:new 0 0 50 50;" xml:space="preserve"><rect x="0" y="10" width="4" height="10" fill="#135F7F" opacity="0.2">  <animate attributeName="opacity" attributeType="XML" values="0.2; 1; .2" begin="0s" dur="0.6s" repeatCount="indefinite" /><animate attributeName="height" attributeType="XML" values="10; 20; 10" begin="0s" dur="0.6s" repeatCount="indefinite" />  <animate attributeName="y" attributeType="XML" values="10; 5; 10" begin="0s" dur="0.6s" repeatCount="indefinite" /></rect><rect x="8" y="10" width="4" height="10" fill="#135F7F"  opacity="0.2">  <animate attributeName="opacity" attributeType="XML" values="0.2; 1; .2" begin="0.15s" dur="0.6s" repeatCount="indefinite" />  <animate attributeName="height" attributeType="XML" values="10; 20; 10" begin="0.15s" dur="0.6s" repeatCount="indefinite" />  <animate attributeName="y" attributeType="XML" values="10; 5; 10" begin="0.15s" dur="0.6s" repeatCount="indefinite" /></rect><rect x="16" y="10" width="4" height="10" fill="#135F7F"  opacity="0.2">  <animate attributeName="opacity" attributeType="XML" values="0.2; 1; .2" begin="0.3s" dur="0.6s" repeatCount="indefinite" />  <animate attributeName="height" attributeType="XML" values="10; 20; 10" begin="0.3s" dur="0.6s" repeatCount="indefinite" />  <animate attributeName="y" attributeType="XML" values="10; 5; 10" begin="0.3s" dur="0.6s" repeatCount="indefinite" /></rect></svg></div>');
                loader.appendTo('#loading');
                document.getElementById("loading").style.display = 'block';
            }

        },
        onSuccess: function (answer) {

            if (modeReturn !== false) {
                eval("response = " + answer.responseText);
                if (response.status == 0) {
                    $j('#' + div).html(convertToHTMLVisibleNewline(response.content));
                    // Old Way
                    //$(div).innerHTML = convertToHTMLVisibleNewline(response.content);
                    //evalMyScripts(div);

                    if (document.getElementById("loading")) {
                        loader.remove();
                        document.getElementById("loading").style.display = 'none';
                    }
                } else {
                    window.top.$('divList').innerHTML = response.error;
                    window.location.href = 'index.php';
                }
            } else {
                $(div).innerHTML = answer.responseText;
                evalMyScripts(div);
            }

        }
    });
}

function loadList2(path, inDiv, modeReturn, init) {

    //alert (modeReturn);
    if (typeof (inDiv) === 'undefined') {
        var div = 'divList';
    } else {
        var div = inDiv;
    }
    if (typeof (modeReturn) === 'undefined') {
        var modeReturn = false;
    }
    if (typeof (init) === 'undefined') {
        window.top.$('main_error').innerHTML = '';
        window.top.$('main_info').innerHTML = '';
    }
    path = path.replace('#', '%23');
    new Ajax.Request(path, {
        method: 'post',
        parameters: {
            url: path
        },
        /* onLoading: function(answer) {
                 //show loading image in toolbar
                 $('loading').style.display='block';
         },*/
        onSuccess: function (answer) {
            if (modeReturn !== false) {
                eval("response = " + answer.responseText);
                if (response.status == 0) {
                    $(div).innerHTML = convertToHTMLVisibleNewline(response.content);
                    evalMyScripts(div);
                } else {
                    window.top.$('main_error').innerHTML = response.error;
                }
            } else {
                $(div).innerHTML = answer.responseText;
                evalMyScripts(div);
            }
            // $('loading').style.display='none';
        }
    });
}

function loadValueInDiv(theId, url) {

    new Effect.toggle('subList_' + theId, 'appear', {
        delay: 0.2
    });

    new Ajax.Request(url, {
        method: 'post',
        parameters: {
            id: theId
        },
        onSuccess: function (answer) {
            // alert(answer.responseText);
            eval("response = " + answer.responseText);
            if (response.status == 0) {
                $('div_' + theId).innerHTML = response.content;
                evalMyScripts('div_' + theId);
            } else {
                window.top.$('main_error').innerHTML = response.error;
            }
        }
    });
}

function CheckUncheckAll(id) {
    if ($(id).checked) {
        checkAll();

    } else {
        unCheckAll();
    }
}

function loadDiffList(id) {
    new Effect.toggle('diffList_' + id, 'appear', {
        delay: 0.2
    });
    var path_manage_script = 'index.php?module=entities&page=loadDiffList&display=true';
    new Ajax.Request(path_manage_script, {
        method: 'post',
        parameters: {
            res_id: id
        },
        onSuccess: function (answer) {
            eval("response = " + answer.responseText);
            $('divDiffList_' + id).innerHTML = '<fieldset style="border: 1px dashed rgb(0, 157, 197);width:100%;"><legend>Liste de diffusion:</legend>' + response.toShow + '</fieldset>';
            new Ajax.Request(path_manage_script, {
                method: 'post',
                parameters: {
                    res_id: id,
                    typeList: 'VISA_CIRCUIT',
                    showStatus: true
                },
                onSuccess: function (answer) {
                    eval("response = " + answer.responseText);
                    if (!response.toShow.match(/<div style="font-style:italic;text-align:center;color:#ea0000;margin:10px;">/)) {
                        $('divDiffList_' + id).innerHTML += '<fieldset id="visa_fieldset" style="border: 1px dashed rgb(0, 157, 197);width:100%;"><legend>Circuit de visa:</legend>' + response.toShow + '</fieldset>';
                        $j('#divDiffList_' + id).css({
                            "width": "97%",
                            "display": "table"
                        });
                        $j('#divDiffList_' + id).children().css({
                            "width": "48%",
                            "display": "table-cell",
                            "vertical-align": "top",
                            "padding": "5px",
                            "margin": "5px"
                        });
                    }
                    new Ajax.Request(path_manage_script, {
                        method: 'post',
                        parameters: {
                            res_id: id,
                            typeList: 'AVIS_CIRCUIT',
                            showStatus: true
                        },
                        onSuccess: function (answer) {
                            eval("response = " + answer.responseText);
                            if (!response.toShow.match(/<div style="font-style:italic;text-align:center;color:#ea0000;margin:10px;">/)) {
                                $('divDiffList_' + id).innerHTML += '<fieldset style="border: 1px dashed rgb(0, 157, 197);width:100%;"><legend>Circuit d\'avis:</legend>' + response.toShow + '</fieldset>';
                                $j('#divDiffList_' + id).css({
                                    "width": "97%",
                                    "display": "table",
                                    "white-space": "nowrap"
                                });
                                $j('#divDiffList_' + id + " fieldset").css({
                                    "white-space": "normal"
                                });
                                if ($j('#visa_fieldset').length) {
                                    $j('#divDiffList_' + id).children().css({
                                        "width": "32%",
                                        "display": "table-cell",
                                        "vertical-align": "top",
                                        "padding": "5px",
                                        "margin": "5px"
                                    });
                                } else {
                                    $j('#divDiffList_' + id).children().css({
                                        "width": "48%",
                                        "display": "table-cell",
                                        "vertical-align": "top",
                                        "padding": "5px",
                                        "margin": "5px"
                                    });
                                }
                            }

                        }
                    });
                }
            });
        }
    });

}

function loadContactsList(id) {
    new Effect.toggle('contactsList_' + id, 'appear', {
        delay: 0.2
    });

    var path_manage_script = 'index.php?page=loadContactsList&display=true';


    new Ajax.Request(path_manage_script, {
        method: 'post',
        parameters: {
            res_id: id
        },
        onSuccess: function (answer) {
            eval("response = " + answer.responseText);
            $('divContactsList_' + id).innerHTML = response.toShow;
        }
    });
}

function loadNoteList(id) {
    new Effect.toggle('noteList_' + id, 'appear', {
        delay: 0.2
    });

    var path_manage_script = 'index.php?page=loadNoteList&display=true';

    new Ajax.Request(path_manage_script, {
        method: 'post',
        parameters: {
            identifier: id
        },
        onSuccess: function (answer) {
            eval("response = " + answer.responseText);
            $('divNoteList_' + id).innerHTML = response.toShow;
        }
    });
}

function showPreviousAttachments(path_manage_script, id) {
    new Effect.toggle('attachList_' + id, 'appear', {
        delay: 0.2
    });

    new Ajax.Request(path_manage_script, {
        method: 'post',
        parameters: {
            res_id_version: id
        },
        onSuccess: function (answer) {
            eval("response = " + answer.responseText);
            $('divAttachList_' + id).innerHTML = response.toShow;
        }
    });
}

function affiche_reference() {
    var nature = document.getElementById('nature_id').options[document.getElementById('nature_id').selectedIndex];

    if (nature.getAttribute('with_reference') == 'true') {
        $j('#reference_number_tr').css('display', 'table-row');
    } else {
        $j('#reference_number_tr').css('display', 'none');
        $j('#reference_number').val('');
    }
}

function erase_contact_external_id(id, erase_id) {
    if ($j('#' + id).val() == '') {
        $j('#' + erase_id).val('');
        if (id == "contact") {
            $j('#contact').css("background-color", "");
        }
    }
}

function purposeCheck() {
    var contact_purpose_id = $('contact_purposes').value;
    var contact_purpose_name = $('new_id').value;
    var path_manage_script = 'index.php?page=purpose_check&dir=my_contacts&display=true';
    if (contact_purpose_name != null && contact_purpose_id == null) {
        $('purpose_to_create').setStyle({
            display: 'table-row'
        });
    } else if (contact_purpose_name != null && contact_purpose_id != null) {
        new Ajax.Request(path_manage_script, {
            method: 'post',
            parameters: {
                contact_purpose: contact_purpose_name,
                contact_purpose_id: contact_purpose_id
            },
            onSuccess: function (answer) {
                eval("response = " + answer.responseText);
                if (response.status == 1) {
                    $('purpose_to_create').setStyle({
                        display: 'table-row'
                    });
                } else if (response.status == 0) {
                    $('purpose_to_create').setStyle({
                        display: 'none'
                    });
                }
            }
        });
    }
}

function simpleAjax(url) {
    new Ajax.Request(url, {
        method: 'post'
    });
}

function loadAddressAttached(contact_id, select) {
    var path_manage_script = 'index.php?display=true&page=select_attachedAddress';
    new Ajax.Request(path_manage_script, {
        method: 'post',
        parameters: {
            contact_id: contact_id,
            select: select
        },
        onSuccess: function (answer) {
            $('selectContactAddress_' + select).innerHTML = answer.responseText;
        }
    });
}

function loadDiffListHistory(listinstance_history_id) {
    new Effect.toggle('diffListHistory_' + listinstance_history_id, 'appear', {
        delay: 0.2
    });

    var path_manage_script = 'index.php?module=entities&page=loadDiffListHistory&display=true';

    new Ajax.Request(path_manage_script, {
        method: 'post',
        parameters: {
            listinstance_history_id: listinstance_history_id
        },
        onSuccess: function (answer) {
            eval("response = " + answer.responseText);
            $('divDiffListHistory_' + listinstance_history_id).innerHTML = response.toShow;
        }
    });
}

function saveSizeInBytes() {
    if (!isNaN($('size_limit_number').value)) {
        if ($('size_format').value == "MB") {
            $('size_limit_hidden').value = $('size_limit_number').value * (1000 * 1000);
        }
        if ($('size_format').value == "GB") {
            $('size_limit_hidden').value = $('size_limit_number').value * (1000 * 1000 * 1000);
        }
        if ($('size_format').value == "TB") {
            $('size_limit_hidden').value = $('size_limit_number').value * (1000 * 1000 * 1000 * 1000);
        }
    } else {
        window.alert('WRONG FORMAT');
    }
}

function convertSize() {
    if (!isNaN($('size_limit_number').value)) {
        if ($('size_format').value == "MB") {
            $('size_limit_number').value = $('size_limit_hidden').value / (1000 * 1000);
            $('actual_size_number').value = $('actual_size_hidden').value / (1000 * 1000);
        }
        if ($('size_format').value == "GB") {
            $('size_limit_number').value = $('size_limit_hidden').value / (1000 * 1000 * 1000);
            $('actual_size_number').value = $('actual_size_hidden').value / (1000 * 1000 * 1000);
        }
        if ($('size_format').value == "TB") {
            $('size_limit_number').value = $('size_limit_hidden').value / (1000 * 1000 * 1000 * 1000);
            $('actual_size_number').value = $('actual_size_hidden').value / (1000 * 1000 * 1000 * 1000);
        }
    } else {
        window.alert('WRONG FORMAT');
    }
}

function setPreviousAddress(address) {
    if (address != "") {
        var allCoordoate = address.split('||');
        $('num').value = allCoordoate[0];
        $('street').value = allCoordoate[1];
        $('add_comp').value = allCoordoate[2];
        $('cp').value = allCoordoate[3];
        $('town').value = allCoordoate[4];
        $('country').value = allCoordoate[5];
        $('website').value = allCoordoate[6];
    }
}

function checkOthersDuplicate(id_form, checkbox_name, radio_name) {

    elem = document.forms[id_form];
    var state = false;
    for (var i = 0; i < elem.length; i++) {
        if (document.forms[id_form][i].name == checkbox_name) {
            document.forms[id_form][i].checked = true;
        } else if (document.forms[id_form][i].name == radio_name && state == false) {
            document.forms[id_form][i].checked = true;
            state = true;
        } else {
            document.forms[id_form][i].checked = false;
        }

        if (document.forms[id_form][i] == radio_name && state == false) {
            document.forms[id_form][i].checked = true;
            state = true;
        }
    }

}

function linkDuplicate(id_form) {
    elem = document.forms[id_form];
    var slave = [];
    var address_del = [];
    var master = '';
    var master_address = '';
    for (var i = 0; i < elem.length; i++) {
        if (document.forms[id_form][i].type == 'checkbox' && document.forms[id_form][i].checked == true) {
            if (document.forms[id_form][i].name == 'delete_address') {
                address_del.push(document.forms[id_form][i].value);
            } else {
                slave.push(document.forms[id_form][i].value);
            }
        }
        if (document.forms[id_form][i].type == 'radio' && document.forms[id_form][i].checked == true) {
            master = document.forms[id_form][i].value;
            master_address = document.getElementById('master_address_fusion_id_' + document.forms[id_form][i].value).value
        }
    }
    var index = slave.indexOf(master);
    slave.splice(index, 1);

    if (slave.length == 0) {
        alert('Aucun contact à fusionner !');
        return false;
    }

    if (master == '') {
        alert('Aucun contact Maitre sélectionné !');
        return false;
    }
    var address_del_string = '';
    address_del_string = address_del.join();
    if (address_del_string != '') {
        address_del_string = '(Les adresses suivantes seront effacées : ' + address_del_string + ')';
    }
    if (confirm('Vous êtes sur le point de substituer les contacts suivants : ' + slave.join() + '\navec le contact : ' + master + ' ' + address_del_string)) {
        var path_manage_script = 'index.php?admin=contacts&page=fusionContact&display=true';
        new Ajax.Request(path_manage_script, {
            method: 'post',
            parameters: {
                master_address_id: master_address,
                slave_contact_id: slave.join(),
                del_address_id: address_del.join(),
                master_contact_id: master
            },
            onSuccess: function (answer) {
                eval("response = " + answer.responseText);
                if (response.status == 0) {
                    console.log(response);
                } else if (response.status == 1) {
                    alert('Erreur!');
                }
            }
        });
        alert('Opération terminée!');
    }
}

function loadTab(resId, collId, titleTab, pathScriptTab, module) {
    if (document.getElementById('show_tab').getAttribute('module') == module) {
        document.getElementById('show_tab').style.display = 'none';
        if (document.getElementById(module + '_tab') != undefined) {
            document.getElementById(module + '_tab').innerHTML = '<i class="fa fa-plus-square"></i>';
        }
        document.getElementById('show_tab').setAttribute('module', '');
        return false;

    }

    var path_manage_script = 'index.php?display=true&page=display_tab';
    $j.ajax({
        url: path_manage_script,
        type: 'POST',
        data: {
            resId: resId,
            collId: collId,
            titleTab: titleTab,
            pathScriptTab: pathScriptTab
        },
        success: function (answer) {
            document.getElementById('show_tab').style.display = 'block';
            document.getElementById('show_tab').setAttribute('module', module);

            $j("span[class='tab_module']").each(function (i, e) {
                e.innerHTML = '<i class="fa fa-plus-square"></i>';
            })
            if (document.getElementById(module + '_tab') != undefined) {
                document.getElementById(module + '_tab').innerHTML = '<i class="fa fa-minus-square"></i>';
            }
            document.getElementById('show_tab').innerHTML = answer;
        }
    });
}

function loadSpecificTab(id_iframe, pathScriptTab) {
    document.getElementById(id_iframe).src = pathScriptTab;
}

function tabClicked(TabId, toHide) {
    var doc = $j("#home-panel");
    if (toHide) {
        doc.css("display", "none");
        $j("#uniqueDetailsDiv").css("display", "");
    } else {
        $j("#uniqueDetailsDiv").css("display", "none");
        doc.css("display", "");
    }

    $j(".DetailsTabFunc").removeClass("TabSelected");
    $j("#" + TabId).addClass("TabSelected");

}

//LOAD BADGES TOOLBAR
function loadToolbarBadge(targetTab, path_manage_script) {

    $j.ajax({
        url: path_manage_script,
        type: 'POST',
        //dataType: 'JSON',
        data: {
            targetTab: targetTab
        },
        success: function (answer) {
            eval("response = " + answer);
            if (response.status == 0) {
                if (response.nav != '') {
                    $j('#' + response.nav).css('paddingRight', '0px');
                }
                eval(response.exec_js);
            }
        },
        error: function (error) {
            alert(error);
        }
    })
}

function resetSelect(id) {
    $j('#' + id).val("");
    $j('#' + id).trigger("chosen:updated");
}

function getChildrenHtml(branch_id, treeDiv, path_manage_script, opened, closed) {
    var minus;
    if ($j('#' + branch_id + ' i').first().prop('class') == closed) {
        minus = false;
    } else {
        minus = true;
    }

    $j.ajax({
        url: path_manage_script,
        type: 'POST',
        data: {
            branch_id: branch_id
        },
        success: function (result) {
            if (minus) {
                BootstrapTree.removeSons($j('#' + branch_id + ' > ul'));
                $j('#' + branch_id + ' i').first().prop('class', closed);
            } else {
                if (result != '') {
                    BootstrapTree.addNode($j('#' + branch_id), $j(result), opened, closed);
                    BootstrapTree.init($j('#' + treeDiv), opened, closed);
                    $j('#' + branch_id + ' > ul').first().find('li').hide();
                    $j('#' + branch_id + ' > ul').first().find('li').show('fast');
                    $j('#' + branch_id + ' i').first().prop('class', opened);
                } else {
                    $j('#' + branch_id + ' i').first().prop('class', 'emptyNode');
                }
            }
        }
    });
}

function titleWithTooltipster(id) {
    $j(document).ready(function () {
        $j('#' + id).tooltipster({
            delay: 0
        });
    });
}

function titleWithTooltipsterClass(className) {
    $j(document).ready(function () {
        $j('.' + className).tooltipster({
            delay: 0
        });
    });
}

/** Advanced Search **/

/**
 * Fills inputs fields of text type in the search form whith value
 *
 * @param values Array Values of the search criteria which must be displayed
 **/
function fill_field_input_text(values) {
    for (var key in values) {
        var tmp_elem = $(key);
        tmp_elem.value = values[key];
    }
}

/**
 * Fills inputs fields of textarea type in the search form whith value
 *
 * @param values Array Values of the search criteria which must be displayed
 **/
function fill_field_textarea(values) {
    for (var key in values) {
        var tmp_elem = $(key);
        tmp_elem.value = values[key];
    }
}

/**
 * Fills date range in the search form whith value
 *
 * @param values Array Values of the search criteria which must be displayed
 **/
function fill_field_date_range(values) {
    for (var key in values) {
        var tmp_elem = $(key);
        tmp_elem.value = values[key][0];
    }
}

/**
 * Selects items in a mutiple list (html select object with multiple) in the search form
 *
 * @param values Array Values of the search criteria which must be displayed
 **/
function fill_field_select_multiple(values) {
    for (var key in values) {
        if (key.indexOf('_chosen') >= 0) {
            var available = key.substring(0, key.length - 7) + '_available';
            var available_list = $(available);
            for (var j = 0; j < values[key].length; j++) {
                for (var i = 0; i < available_list.options.length; i++) {
                    if (values[key][j] == available_list.options[i].value) {
                        available_list.options[i].selected = 'selected';
                    }
                }
            }
            Move_ext(available, key);
        }
        if (key.indexOf('_targetlist') >= 0) {
            var available = key.substring(0, key.length - 7) + '_sourcelist';
            var available_list = $(available);
            for (var j = 0; j < values[key].length; j++) {
                if (available_list) {
                    for (var i = 0; i < available_list.options.length; i++) {
                        if (values[key][j] == available_list.options[i].value) {
                            available_list.options[i].selected = 'selected';
                        }
                    }
                }
            }
            if (available) {
                Move_ext(available, key);
            }
        }
    }
}

/**
 * Selects an item in a simple list (html select object) in the search form
 *
 * @param values Array Values of the search criteria which must be displayed
 **/
function fill_field_select_simple(values) {
    for (var key in values) {
        var tmp_elem = $(key);
        for (var j = 0; j < values[key].length; j++) {
            for (var i = 0; i < tmp_elem.options.length; i++) {
                if (values[key][j] == tmp_elem.options[i].value) {
                    tmp_elem.options[i].selected = 'selected';
                }
            }
        }
    }
}

/**
 * Load a query in the Advanced Search page
 *
 * @param valeurs Array Values of the search criteria which must be displayed
 * @param loaded_query Array Values of the search criteria
 * @param id_form String Search form identifier
 * @param ie_browser Bool Browser is internet explorer or not
 * @param error_ie_txt String Error message specific to ie browser
 **/
function load_query(valeurs, loaded_query, id_form, ie_browser, error_ie_txt) {
    for (var critere in loaded_query) {
        if (valeurs[critere] != undefined) // in the valeurs array
        {
            add_criteria('option_' + critere, id_form, ie_browser, error_ie_txt);
        }
        eval("processingFunction=fill_field_" + loaded_query[critere]['type']);
        if (typeof (processingFunction) == 'function') // test if the funtion exists
        {
            processingFunction(loaded_query[critere]['fields']);
        }
    }
}

/**
 * Adds criteria in the search form
 *
 * @param elem_comp String Identifier of the option of the criteria list to displays in the search form
 * @param id_form String Search form identifier
 * @param ie_browser Bool Is the browser internet explorer or not
 * @param error_txt_ie String Error message specific to ie browser
 **/
function add_criteria(elem_comp, id_form, ie_browser, error_txt_ie) {
    // Takes the id of the chosen option which must be one of the valeurs array
    var elem = elem_comp.substring(7, elem_comp.length);
    var form = window.$(id_form);
    var valeur = valeurs[elem];
    if (ie_browser) {
        var div_node = $j('#search_parameters_' + elem);
    }
    if (typeof (valeur) != 'undefined') {
        if (ie_browser == true && typeof (div_node) != 'undefined' && div_node != null) {
            alert(error_txt_ie);
        } else {
            var node = document.createElement('div');
            node.setAttribute('id', 'search_parameters_' + elem);
            var tmp = '<table width="100%" border="0"><tr><td width="30%">';
            tmp += '<i class="fa fa-angle-right"></i> ' + valeur['label'];
            tmp += '</td><td>';
            tmp += valeur['value'];
            tmp += '</td><td width="30px">';
            tmp += '<a href="#" onclick="delete_criteria(\'' + elem + '\', \'';
            tmp += id_form + '\');return false;">';
            tmp += '<i class="fa fa-times fa-2x"></i></a>';
            tmp += '</td></tr></table>';
            // Loading content in the page
            node.innerHTML = tmp;
            form.appendChild(node);
            var label = $(elem_comp);
            if (elem_comp == 'option_signatory_name' || elem_comp == 'option_visa_user') {
                loadAutocompletionScript(elem_comp);
            }

            label.parentNode.selectedIndex = 0;
            label.style.display = 'none';
        }
    }
}

function loadAutocompletionScript(optionId) {
    infos = $j('#' + optionId).data('load');
    initList_hidden_input(infos.id, infos.idList, infos.config + 'index.php?display=true&dir=indexing_searching&page=users_list_by_name_search', 'what', "2", infos.autocompleteId);
}


/**
 * Deletes a criteria in the search form
 *
 * @param elem_comp String Identifier of the option of the criteria list to delete in the search form
 * @param id_form String Search form identifier
 **/
function delete_criteria(id_elem, id_form) {
    var form = $(id_form);
    var tmp = (id_elem.indexOf('option_') >= 0) ? id_elem : 'option_' + id_elem;
    var label = $(tmp);
    label.style.display = '';
    //  label.disabled = !label.disabled;
    tmp = (id_elem.indexOf('option_') >= 0) ? id_elem.substring(7, id_elem.length) : id_elem;
    //form.removeChild($('search_parameters_'+tmp));
    $('search_parameters_' + tmp).parentElement.removeChild($('search_parameters_' + tmp));
}

/**
 * Validates the search form, selects all list options ending with _chosen (type select_multiple) to avoid missing elements
 *
 * @param id_form String Search form identifier
 **/
function valid_search_form(id_form) {
    var frm = $(id_form);
    var selects = frm.getElementsByTagName('select'); //Array
    for (var i = 0; i < selects.length; i++) {
        if (selects[i].multiple && (selects[i].id.indexOf('_chosen') >= 0 ||
                selects[i].id.indexOf('_targetlist') >= 0)) {
            selectall_ext(selects[i].id);
        }
    }
}

/**
 * Clears the search form : delete all optional criteria in the form
 *
 * @param id_form String Search form identifier
 * @param id_list String Criteria list identifier
 **/
function clear_search_form(id_form, id_list) {
    var list = $(id_list);
    for (var i = 0; i < list.options.length; i++) {
        if (list.options[i].style.display == 'none') {
            delete_criteria(list.options[i].id, id_form);
        }
    }
    var elems = document.getElementsByTagName('INPUT');
    for (var i = 0; i < elems.length; i++) {
        if (elems[i].type == 'text') {
            elems[i].value = '';
        }
    }
    var lists = document.getElementsByTagName('SELECT');
    for (var i = 0; i < lists.length; i++) {
        lists[i].selectedIndex = 0;
    }
    var copie_false = $('copies_false');
    if (copie_false) {
        copie_false.checked = true;
    }
}

/**
 * Clears the queries list : remove an option in this list
 *
 * @param item_value String Identifier of the item to remove
 **/
function clear_q_list(item_value) {
    var query = $('query');

    if (item_value && item_value != '' && query) {
        var item = $('query_' + item_value);
        if (item) {
            query.removeChild(item);
        }
    }
    if (query && query.options.length > 1) {
        var q_list = $('default_query');
        if (q_list) {
            q_list.selected = 'selected';
        }
        var del_button = $('del_query');
        if (del_button) {
            del_button.style.display = 'none';
        }
    } else {
        var div_query = $('div_query');
        if (div_query) {
            div_query.style.display = 'none';
        }
    }
}

/**
 * Load a saved query in the Advanced Search page (using Ajax)
 *
 * @param id_query String Identifier of the saved query to load
 * @param id_form_to_load String Identifier of the search form
 * @param sql_error_txt String SQL error message
 * @param server_error_txt String Server error message
 * @param manage_script String Ajax script
 **/
function load_query_db(id_query, id_list, id_form_to_load, sql_error_txt, server_error_txt, manage_script) {
    if (id_query != '') {
        new Ajax.Request(
            manage_script, {
                method: 'post',
                parameters: {
                    id: id_query,
                    action: 'load'
                },
                onSuccess: function (answer) {
                    eval("response = " + answer.responseText + ';');
                    if (response.status == 0) {
                        clear_search_form(id_form_to_load, id_list);
                        //Clears the search form
                        if (response.query instanceof Object &&
                            response.query != {}) {
                            load_query(valeurs, response.query, id_form_to_load);
                        }
                        $j("#del_query").css("display", "inline");
                        $j("#query_" + id_query)[0].selected = true;
                    } else if (response.status == 2) {
                        $('error').update(sql_error_txt);
                    } else {
                        $('error').update(server_error_txt);
                    }
                },
                onFailure: function () {
                    $('error').update(server_error_txt);
                }
            });
    }
}

/**
 * Delete a saved query in the database (using Ajax)
 *
 * @param id_query String Identifier of the saved query to delete
 * @param id_list String Identifier of the queries list
 * @param id_form_to_load String Identifier of the search form
 * @param sql_error_txt String SQL error message
 * @param server_error_txt String Server error message
 * @param path_script String Ajax script
 **/
function del_query_db(id_query, id_list, id_form_to_load, sql_error_txt, server_error_txt, path_script) {
    if (id_query != '') {
        var query_object = new Ajax.Request(
            path_script, {
                method: 'post',
                parameters: {
                    id: id_query.value,
                    action: "delete"
                },
                onSuccess: function (answer) {

                    eval("response = " + answer.responseText + ';');
                    if (response.status == 0) {
                        clear_search_form(id_form_to_load, id_list); //Clears search form
                        clear_q_list(id_query.value);
                    } else if (response.status == 2) {
                        $('error').update(sql_error_txt);
                    } else {
                        $('error').update(server_error_txt);
                    }
                },
                onFailure: function () {
                    $('error').update(server_error_txt);
                }
            });
    }
}

/** Old MaarchJs **/

// Fonction pour gérer les changements dynamiques de sous-menu.
// Prend en variable le numéro du sous-menu à afficher.

function ChangeH2(objet) {
    if (objet.getElementsByTagName('img')[0].src.indexOf("plus") > -1) {
        objet.getElementsByTagName('img')[0].src = "img/moins.png";
        objet.getElementsByTagName('span')[0].firstChild.nodeValue = " ";
    } else {
        objet.getElementsByTagName('img')[0].src = "img/plus.png";
        objet.getElementsByTagName('span')[0].firstChild.nodeValue = " ";
    }
}

function Change2H2(objet) {
    if (objet.getElementsByTagName('img')[0].src.indexOf("folderopen") > -1) {
        objet.getElementsByTagName('img')[0].src = "img/folder.gif";
        objet.getElementsByTagName('span')[0].firstChild.nodeValue = " ";
    } else {
        objet.getElementsByTagName('img')[0].src = "img/folderopen.gif";
        objet.getElementsByTagName('span')[0].firstChild.nodeValue = " ";
    }
}

var initialized = 0;
var etat = new Array();

function reinit() {
    initialized = 0;
    etat = new Array();
}

function initialise() {
    for (var i = 0; i < 500; i++) {
        etat[i] = new Array();
        etat[i]["h2"] = document.getElementById('h2' + i);
        etat[i]["desc"] = document.getElementById('desc' + i);
        etat[i]["etat"] = 0;
    }
    initialized = 1;
}

function ferme(id) {
    Effect.SlideUp(etat[id]["desc"]);
    ChangeH2(etat[id]["h2"]);
    etat[id]["etat"] = 0;
}

function ouvre(id) {
    Effect.SlideDown(etat[id]["desc"]);
    ChangeH2(etat[id]["h2"]);
    etat[id]["etat"] = 1;
}

function ferme2(id) {
    Effect.SlideUp(etat[id]["desc"]);
    Change2H2(etat[id]["h2"]);
    etat[id]["etat"] = 0;
}

function ouvre2(id) {
    Effect.SlideDown(etat[id]["desc"]);
    Change2H2(etat[id]["h2"]);
    etat[id]["etat"] = 1;
}

function ferme3(id) {
    Effect.SlideUp(etat[id]["desc"]);
    etat[id]["etat"] = 0;
}

function ouvre3(id) {
    Effect.SlideDown(etat[id]["desc"]);
    etat[id]["etat"] = 1;
}

function change(id) {
    if (!initialized) {
        initialise()
    }
    // alert(etat[id]["etat"]);
    if (etat[id]["etat"]) {
        ferme(id);
    } else {
        for (var i = 0; i < etat.length; i++) {
            if (etat[i]["etat"]) {
                ferme(i);
            }
        }
        ouvre(id);
    }
}

function change2(id) {
    if (!initialized) {
        initialise()
    }
    if (etat[id]["etat"]) {
        ferme2(id);
    } else {
        ouvre2(id);
    }
}

function change3(id) {
    if (!initialized) {
        initialise()
    }
    // alert(etat[id]["etat"]);
    if (etat[id]["etat"]) {
        ferme3(id);
    } else {
        for (var i = 0; i < etat.length; i++) {
            if (etat[i]["etat"]) {
                ferme3(i);
            }
        }
        ouvre3(id);
    }
}

function print_current_result_list(path) {
    alert("Cela imprimera la liste actuellement affichée");
    var mywindow = window.open('', 'my div', 'height=400,width=600');
    mywindow.document.write('<html><head><title>MAARCH</title>');
    mywindow.document.write('<link rel="stylesheet" href="../../node_modules/@fortawesome/fontawesome-free/css/all.css" type="text/css" media="all"/>');
    mywindow.document.write('<link rel="stylesheet" href="' + path + '/css/font-awesome-maarch/css/font-maarch.css" type="text/css" media="all"/>');
    mywindow.document.write('<link rel="stylesheet" href="' + path + '/merged_css.php" type="text/css" media="all"/>');
    mywindow.document.write('</head><body >');
    mywindow.document.write('<style>.check,#checkUncheck{display:none;}</style>');
    mywindow.document.write($j('<div/>').append($j('#extended_list').clone()).html());
    mywindow.document.write('</body></html>');
    mywindow.document.close();
    mywindow.focus();
    setTimeout(function () {
        mywindow.print();
    }, 500);
    window.onfocus = function () {
        setTimeout(function () {
            mywindow.close();
        }, 500);
    }
}

function toggleRefMaarch() {
    if ($j('#refMaarch').is(":checked")) {
        $j('#refSearch').css({
            "display": "flex"
        });
        $j(".refMaarch input").addClass("readonly");
        $j(".refMaarch input").prop("readonly", true);
    } else {
        $j('#refSearch').hide();
        $j(".refMaarch input").removeClass("readonly");
        $j(".refMaarch input").prop("readonly", false);
    }
}

function setRefAdresse(item) {
    $j("#ban_id").val(item.banId);
    $j(".refMaarch #num").val(item.number);
    $j(".refMaarch #street").val(item.afnorName);
    $j(".refMaarch #cp").val(item.postalCode);
    $j(".refMaarch #town").val(item.city);
    $j(".refMaarch #country").val("FRANCE");
}

function toggleBlock(div, divIcon) {
    $j('#' + div).slideToggle('slow');
    if ($j('#' + divIcon + ' i').hasClass('fa-minus-square')) {
        $j('#' + divIcon + ' i').removeClass('fa-minus-square');
        $j('#' + divIcon + ' i').addClass('fa-plus-square');
    } else {
        $j('#' + divIcon + ' i').removeClass('fa-plus-square');
        $j('#' + divIcon + ' i').addClass('fa-minus-square');
    }
}

function loadTypeahead(input, order, dynamic, url) {
    $j.typeahead({
        input: input,
        order: order,
        dynamic: dynamic,
        debug: false,
        source: {
            ajax: function (query) {
                return {
                    type: 'POST',
                    url: url,
                    data: {
                        Input: "{{query}}"
                    }
                }
            }
        }
    });
}

function reloadTypeahead(elem) {
    $j(".typeahead__result").remove();
    $j("#searchAddress").val('');
    $j("#searchAddress").attr("placeholder", "Rechercher dans le référentiel du " + $j('#' + elem.id).val());
    $j("#searchAddress").typeahead({
        delay: '500',
        minLength: 3,
        order: "asc",
        maxItem: 10,
        filter: false,
        dynamic: true,
        display: "address",
        templateValue: "{{address}}",
        emptyTemplate: "Aucune adresse n'existe avec <b>{{query}}</b>",
        source: {
            ajax: {
                type: "GET",
                dataType: "json",
                url: "../../rest/autocomplete/banAddresses",
                data: {
                    address: "{{query}}",
                    department: $j('#' + elem.id).val()
                }
            }
        },
        callback: {
            onClickAfter: function (node, a, item, event) {
                setRefAdresse(item);
            }
        }
    });
}

function checkMultiIndexingGroup(url) {
    $j.ajax({
        url: '../../rest/header',
        type: 'get',
        data: {},
        success: function (answer) {
            if (answer.user.indexingGroups.length > 1) {
                var link = '';
                answer.user.indexingGroups.each(function (group, key) {
                    link += '<li style="padding:5px;"><a href="'+url+'&groupId='+group.groupId+'">'+group.label+'</a></li>';
                });  
                var modal = $j('<div id="indexingGroupSelect" class="modal" style="position: fixed;left: 50%;transform: translate(-50%, -50%);top: 25%;"><h2>Profil d\'indexation :</h2><ul style="text-align:left;font-size: 24px;">'+link+'</ul><div style="display: flex;justify-content: center;"><input style="width: 70px;text-align: center;" class="button" value="Annuler" onclick="$j(\'#indexingGroupSelect\').remove();$j(\'#maarch_content\').css({opacity:1})"/></div></div>');
                modal.appendTo('body');
                $j('#maarch_content').css({opacity:0.2});
            } else {
                window.location.href=url;
            }
        }
    });
}

function writeLocationBar(path,label,level) {
    if ($j('#ariane a:last').length == 0) {
        var home = $j('<a href="'+path+'" onclick="localStorage.setItem(\'PreviousV2Route\', \'/home\');">'+label+'</a>');
        home.appendTo('#ariane');
    } else {
        var elem = $j('<a href="'+path+'&level='+level+'">'+label+'</a>');
        elem.insertAfter('#ariane a:last');
        var separator = $j('<span> > </span>');
        separator.insertBefore(elem);
    }    
}

function contactMapping(fieldsCtrl, formId) {
    for (var j = 0, fieldsCtrlElem; fieldsCtrlElem = fieldsCtrl[j++];) {
        if (fieldsCtrlElem === "department") {
            fieldsCtrl[j-1] = "departement";
        }
        if (fieldsCtrlElem === "address_complement") {
            fieldsCtrl[j-1] = "add_comp";
        }
        if (fieldsCtrlElem === "address_num") {
            fieldsCtrl[j-1] = "num";
        }
        if (fieldsCtrlElem === "address_street") {
            fieldsCtrl[j-1] = "street";
        }
        if (fieldsCtrlElem === "address_postal_code") {
            fieldsCtrl[j-1] = "cp";
        }
        if (fieldsCtrlElem === "address_town") {
            fieldsCtrl[j-1] = "town";
        }
        if (fieldsCtrlElem === "address_country") {
            fieldsCtrl[j-1] = "country";
        }
        if (fieldsCtrlElem === "email") {
            fieldsCtrl[j-1] = "mail";
        }
        if (fieldsCtrlElem === "add_comp") {
            fieldsCtrl[j-1] = "comp_data";
        }
        if (fieldsCtrlElem === "other_data") {
            fieldsCtrl[j-1] = "comp_data";
        }
    }
    var elements = document.getElementById(formId).elements;

    for (var i = 0, element; element = elements[i++];) {
        for (var j = 0, fieldsCtrlElem; fieldsCtrlElem = fieldsCtrl[j++];) {
            if (element.name === fieldsCtrlElem) {
                element.style.borderWidth = "2px";
                element.style.borderColor = "#CCFFCC";
            }
        }
    }
}

function switchAutoCompleteType(id, mode, alternateVersion) {
    if (mode == 'contactsUsers' && $j('#'+id+'_icon_contactsUsers').css('color') != 'rgb(19, 95, 127)') {
        $j('#'+id+'_icon_contactsUsers').css({'color' : '#135F7F'});
        $j('#'+id+'_icon_entities').css({'color' : '#666'});
        $j('#'+id+'').attr('placeholder','Rechercher un contact / utilisateur');
        $j(".typeahead__result").remove();
        initSenderRecipientAutocomplete(id, 'contactsUsers', alternateVersion);
        $j('#'+id).val('');
        $j('#'+id+'_id').val('');
        $j('#'+id+'_type').val('');
        $j('#'+id+'').css('background-color', 'white');
    } else if (mode == 'entities' && $j('#'+id+'_icon_entities').css('color') != 'rgb(19, 95, 127)') {
        $j('#'+id+'_icon_contactsUsers').css({'color' : '#666'});
        $j('#'+id+'_icon_entities').css({'color' : '#135F7F'});
        $j('#'+id+'').attr('placeholder','Rechercher une entité');
        $j(".typeahead__result").remove();
        initSenderRecipientAutocomplete(id, 'entities', alternateVersion);
        $j('#'+id).val('');
        $j('#'+id+'_id').val('');
        $j('#'+id+'_type').val('');
        $j('#'+id+'').css('background-color', 'white');
    }
}

function openSenderInfoContact(id, type) {
    var height = '';
    if (id == '') {
        alert('Aucun contact sélectionné');
        return false;
    }
    if(type == 'entity'){
        alert('Aucune information disponible pour les entités');
    } else {
        if(type == 'user'){
            height = '400';
        } else {
            height = '800';
        }
        window.open('index.php?display=true&dir=my_contacts&page=info_contact_iframe&mode=editDetailSender&editDetailSender&popup&sender_recipient_id='+id+'&sender_recipient_type='+type, 'contact_info', 'height='+height+', width=1000,scrollbars=yes,resizable=yes');
    }
}

function displayAddMailing() {
    if ($j("#mailingInfo").is(":visible")) {
        $j("#addMailing").show();
    }
}


function setSendAttachment(id, isVersion) {
    $j.ajax({
        url: '../../rest/attachments/' + id + '/inSendAttachment',
        type: 'PUT',
        dataType: 'json',
        data: {
            isVersion: isVersion
        },
        success: function (answer) {
            if (typeof window.parent['angularSignatureBookComponent'] !== "undefined") {
                window.parent.angularSignatureBookComponent.componentAfterAttach("left");
            }
        },
        error: function (err) {
            alert("Une erreur s'est produite : " + err.responseJSON.exception[0].message);
        }
    });
}

function uploadFiles () {

    var fileInfo = $j("#file")[0]["files"][0];

    var extension = "";

    // set extension according to filename
    if (fileInfo.name.split('.').length > 1) {
        extension = fileInfo.name.split('.').pop();
    }
    
    $j.ajax({
        url: '../../rest/resources/checkFileUpload',
        type: 'POST',
        dataType: "json",
        data: {
            extension : extension,
            size : fileInfo.size,
            type : fileInfo.type,
        },
        success: function (answer) {
            $j('#with_file')[0].value='false';
            $j("#select_file_form").attr('method','post');
            $j("#select_file_form").submit();
        },
        error: function (err) {
            alert(err.responseJSON.errors);
        }
    });
}