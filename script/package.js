/*
 * Rehandling default error handler
 */
function handleError(eMsg, eFile, eLine)
{
    //$.post("/?error", {msg: eMsg, file: eFile, line: eLine});
}
window.onerror = handleError;
/*
 * Default data kit
 */
defkit = function() {};
defkit.reload = function() {
    document.location.reload();
    return false;
}
defkit.barShow = function() {
    $("#ajaxloader").show().animate({top:"0px"}, {queue:false, duration:400});
}
defkit.barHide = function() {
    $("#ajaxloader").animate({top:"-20px"}, {queue:false, duration:400});
}
defkit.dialogclose = function() {
    $('#modalwindow').dialog('close');
    return false;
}
defkit.initSelect = function() {
    $("select:not(.fine)").customSelect();
    return false;
}
/*
 * Announce data kit
 */
annkit = function(){};
annkit.listcat = function(currentList) {
    if (currentList.value == "") {
        $(currentList).next().nextAll("select, span.customSelect").remove();
        return false;
    }
    defkit.barShow();
    $.get('/ajax/field_category&id=' + currentList.value, function(response) {
        $(currentList).next().nextAll("select, span.customSelect").remove();
        if (response) {
            $(currentList).parent().append(response);
            $('#action').load('/ajax/field_action&id=' + currentList.value);
        }
        defkit.initSelect();
        defkit.barHide();
    });
}
annkit.listcatcom = function(currentList) {
    if (currentList.value == "") {
        $(currentList).next().nextAll("select, span.customSelect").remove();
        return false;
    }
    defkit.barShow();
    $.get('/ajax/field_category_comp&id=' + currentList.value, function(response) {
        $(currentList).next().nextAll("select, span.customSelect").remove();
        if (response) {
            $(currentList).parent().append(response);
        }
        defkit.initSelect();
        defkit.barHide();
    });
}
/*
 * Administrative data kit
 */
admkit = function() {};
admkit.roove = function(remove, reload) {
    if (remove) $(remove).remove();
    if (reload) document.location.reload();
}
admkit.approove = function(mode, id, reload) {
    url = "/admin/moder=" + mode + "&aj&approove&roove=" + id;
    $.get(url, function(request){
        console.log(request);
        admkit.roove("#block" + id, reload && (request == true));
    });
    return false;
}
admkit.deproove = function(mode, id, reload) {
    url = "/admin/moder=" + mode + "&aj&deproove&roove=" + id;
    $.get(url, function(request){
        admkit.roove("#block" + id, reload && (request == true));
    });
    return false;
}
admkit.hidroove = function(id) {
    admkit.roove("#block" + id, false);
    return false;
}
/*
 * Locale worktime kits
 */
worktime = function() {};
worktime.update = function (request, dayno) {
    $("#wt_day_" + dayno).html(request);
    return false;
}
worktime.timeget = function(dayno) {
    DialogSilent("/cabcomp/worktime/timeget&day=" + dayno, 290, null, "Изменение режима работы");
    return false;
}
worktime.timeset = function(dayno) {
    ajaxRequestByForm(timeset, "/cabcomp/worktime/timeset&day=" + dayno, worktime.update, [dayno]);
    defkit.dialogclose();
    return false;
}
worktime.timeclear = function(dayno) {
    ajaxRequest("/cabcomp/worktime/timeclear&day=" + dayno, worktime.update, [dayno]);
    return false;
}
worktime.timecopy = function(dayno) {
    ajaxRequest("/cabcomp/worktime/timecopy&day=" + dayno, defkit.reload, [dayno]);
    return false;
}




/* reuse  */



function AnQueryChangePage(PageID)
{
    $("#page").val(PageID);
}

function AnQueryFilter()
{
    filter = document.URL.replace(/#/, "") + "&aj&" + $('#form-order').serialize() + '&' + $('#form-filter').serialize();

    //filter = filter + "#" + $('#form-order').serialize() + '&' + $('#form-filter').serialize();
    //document.location = filter;
    //return false;

    defkit.barShow();
    data = $.getJSON(filter, function(Request) {
        $('#announce-data').html(Request[0]);
        $('#pageselector, #pageselector-bottom').html(Request[1]);
        $('#announce-count').html(Request[2]);
        $("img.lazy").lazyload();
        scroll(0, 0);
        defkit.barHide();

        /*todo refactor */
        $("[title]").tooltip({
            position: "left center",
            //offset: [-35, 0]
        });
    });

    return false;
}

function AnQueryFilterPhoto()
{
    AnQueryChangePage(0)
    AnQueryFilter();

    return true;
}

function AnQueryPager(PageID)
{
    AnQueryChangePage(PageID)
    AnQueryFilter();

    return false;
}

function AnQueryTyped(TypeID)
{
    $("#fl_type").val(TypeID);
    AnQueryChangePage(0)
    AnQueryFilter();

    $("#typeaction a").removeClass("link-hover").addClass("link");
    $("#ta_" + TypeID).toggleClass("link-hover");

    return false;
}

function AnQueryGallery(TypeID)
{
    $("#fl_gallery").val(TypeID);
    AnQueryChangePage(0)
    AnQueryFilter();

    $("#gallery-block div").removeClass("link-hover");
    $("#ta_" + TypeID).toggleClass("link-hover");

    return false;
}

function AnQueryOrder(Object)
{
    var classUp = 'order-up';
    var classDn = 'order-dn';
    var classNn = 'order-nn';
    var Link = $(Object).parent();
    var LinkUp = Link.hasClass(classUp);
    $('.orderlist-table div').removeClass(classUp).removeClass(classDn);

    if (!LinkUp) {
        $('#osc').val(0);
        Link.addClass(classUp);
    } else {
        $('#osc').val(1);
        Link.addClass(classDn);
    }
    $('#oby').val(Object.id);

    return AnQueryFilter();
}

function AnQueryFavourite(Object, AnnounceID)
{
    var classOn = "fav-on";
    var classOff = "fav-off";
    var link = $(Object);

    defkit.barShow();

    if (link.hasClass(classOn)) {
        $.get('/ajax/favourite_delete&id=' + AnnounceID, [],
            function(Response) {
                link.toggleClass(classOn);
                link.toggleClass(classOff);
                $("#countfav").html(" ("+Response+")");
                defkit.barHide();
            }
        );
    } else {
        $.get('/ajax/favourite_announce&id=' + AnnounceID, [],
            function(Response) {
                link.toggleClass(classOff);
                link.toggleClass(classOn);
                $("#countfav").html(" ("+Response+")");
                defkit.barHide();
            }
        );
    }

    return false;
}

function AdQueryComment(AnnounceID)
{
    //todo comment
    var postValue = $('#textdata').val();
    if (postValue.length < 10) return false;

    defkit.barShow();
    $.post('/ajax/comment_announce&id=' + AnnounceID, {textdata: postValue},
        function(Response) {
            if (!isNaN(parseInt(Response))) {
                document.location.reload();
            } else {
                alert('Ошибка добавления комментария');
            }
            defkit.barHide();
        }
    );
    return false;
}



function CreateGallery()
{
    var item = $("#gallery").find("li");

    if (item.length > 3) {
        $("#gallery").prettyGallery({
            itemsPerPage : 3,
			navigation : 'bottom',
			of_label: ' из ',
			previous_title_label: 'Назад',
			next_title_label: 'Вперед',
			previous_label: 'Назад',
			next_label: 'Вперед'
        });
    }

    $("#gallery a[rel^='prettyPhoto'], #photoblock a[rel^='prettyPhoto']").prettyPhoto({
        allow_resize: false,
        slideshow: 2000,
        deeplinking: false
    });
}



function CreatePhoto(oObj)
{
    $(oObj).prettyPhoto({
        allow_resize: false,
        deeplinking: false
    });
}

function CreatePhotoLoader(place, size, count, param, files)
{
    $(place).each(function () {
        if (typeof File === 'undefined') { $(this).find('input:file').each(function() { $(this).removeAttr('multiple').replaceWith($(this).clone(true));});}
    }).fileupload({
        maxFileSize: 3145728, previewMaxHeight: size, previewMaxWidth: size, autoUpload: true, paramName: param,
        url: "/ajax/uploadimg", maxNumberOfFiles: count, acceptFileTypes: /(\.|\/)(gif|png|jpg|jpeg)$/i,
    });

    var fu = $(place).data("fileupload");
    fu._adjustMaxNumberOfFiles(-files.length);
    fu._renderDownload(files).appendTo($(place + " .files"))
        .fadeIn(function () { $(this).show();
    });
    if (files.length > 1) {
        $(place).find(".fileupload-buttonbar .delete").show()
    }
    $(place).bind("fileuploadadd", function (e, data) {
        if ($(place + ' tr').length >= 1) $(this).find(".fileupload-buttonbar .delete").show();
    });
    $(place).delegate('td button', 'click', function(e) {
        if ($(place + ' tr').length <= 2) $(place + " .fileupload-buttonbar .delete").hide();
    });
}

(function($){
 $.fn.extend({
 	customSelect : function(options) {
	  if(!$.browser.msie || ($.browser.msie&&$.browser.version>6)){
	  return this.each(function() {
        if (!$(this).hasClass("styled")) {
			var currentSelected = $(this).find(':selected');
			$(this).after('<span class="customSelect"><span class="customStyleSelectBoxInner">'+currentSelected.text()+'</span></span>').css({position:'absolute', opacity:0});
            $(this).toggleClass("styled");
			var selectBoxSpan = $(this).next();
			var selectBoxWidth = 12 + parseInt($(this).width()) - parseInt(selectBoxSpan.css('padding-left')) -parseInt(selectBoxSpan.css('padding-right'));
			var selectBoxSpanInner = selectBoxSpan.find(':first-child');
			selectBoxSpanInner.css({width:selectBoxWidth, display:'inline-block'});
			var selectBoxHeight = parseInt(selectBoxSpan.height()) + parseInt(selectBoxSpan.css('padding-top')) + parseInt(selectBoxSpan.css('padding-bottom'));
			$(this).height(selectBoxHeight).change(function(){
				selectBoxSpanInner.text($('option:selected',this).text()).parent().addClass('changed');
			});
        }
	  });
	  }
	}
 });
})(jQuery);

function CreateCaptcha()
{
    $("#caplink").click(function(){
        $("#capimage").attr("src", "/images/ajax-loader.gif").attr("src", "/ajax/captcha?t=" + Math.random());
        return false;
    });
    $("#capimage").attr("title", "Нажмите для смены капчи");
}

function CreateFormTrade()
{
    //form-tradepost
    var form = $("#form-trade");
    if (form.size() > 0) {
        $("#form-trade #name").addClass("validate[required, minSize[3]]");
        $("#form-trade #phone").addClass("validate[required, minSize[3]]");
        $("#form-trade #email").addClass("validate[required, minSize[4], custom[email]]");
        $("#form-trade #captcha").addClass("validate[required, ajax[ajaxCaptcha]]");
        $("#form-trade").validationEngine({ajaxValidCache: {captcha: false}});
    }
}


$(document).ready(function() {

    CreateCaptcha();

    $("#searchsample").click(function(){
        $("#fl_text").val(this.innerHTML);
        return false;
    });

    //todo reuse + doubling
    ContactInit();

    // jquery photo load
    $("img.lazy").lazyload();

    // wisywig editor
    $("textarea.bbcode").sceditorBBCodePlugin({
        style: "/script/wyisiwyg/jquery.sceditor.default.min.css",
        toolbar: "bold,italic,underline,strike|left,center,right,justify|bulletlist,orderedlist|table,horizontalrule|undo,redo,removeformat|source",
        emoticons: {},
        locale: "ru"
    });

    $("textarea.bbcodefull").sceditorBBCodePlugin({
        style: "/script/wyisiwyg/jquery.sceditor.default.min.css",
        emoticons: {},
        locale: "ru"
    });

    // todo separate
    // company-register
    var form = $("#company-register");
    if (form.size() > 0) {
        $("#company-register #caption").addClass("validate[required, minSize[3]]");
        $("#company-register #subdomain").addClass("validate[minSize[3]]");
        $("#company-register #category").addClass("validate[required]");
        $("#company-register #captcha").addClass("validate[required, ajax[ajaxCaptcha]]");
        $("#company-register").validationEngine();
    }
    // form-passlost
    var form = $("#form-passlost");
    if (form.size() > 0) {
        $("#form-passlost #captcha").addClass("validate[required, ajax[ajaxCaptcha]]");
        $("#form-passlost").validationEngine();
    }
    // form-signup
    var form = $("#form-signup");
    if (form.size() > 0) {
        $("#form-signup #login").addClass("validate[required, minSize[3], custom[onlyLetterNumber], ajax[ajaxLogin]]");
        $("#form-signup #mail").addClass("validate[required, minSize[4], custom[email], ajax[ajaxEmail]]");
        $("#form-signup #pwd").addClass("validate[required, minSize[4]]");
        $("#form-signup #captcha").addClass("validate[required, ajax[ajaxCaptcha]]");
        $("#form-signup").validationEngine();
    }
    // form-announce
    var form = $("#form-announce");
    if (form.size() > 0) {
        $("#form-announce #mail").addClass("validate[minSize[4], custom[email], ajax[ajaxEmail]]");
        $("#form-announce #city").addClass("validate[required]");
        $("#form-announce #division").addClass("validate[required]");
        $("#form-announce #category").addClass("validate[required]");
        $("#form-announce #caption").addClass("validate[required, minSize[5], maxSize[90]]");
        $("#form-announce #cost").addClass("validate[custom[number]]");
        $("#form-announce #action").addClass("validate[required]");
        $("#form-announce #captcha").addClass("validate[required, ajax[ajaxCaptcha]]");
        $("#form-announce").validationEngine({ajaxValidCache: {captcha: false}});
    }
    // form-announce-fast
    var form = $("#form-announce-fast");
    if (form.size() > 0) {
        $("#form-announce-fast #mail").addClass("validate[minSize[4], custom[email], ajax[ajaxEmail]]");
        $("#form-announce-fast #city").addClass("validate[required]");
        $("#form-announce-fast #division").addClass("validate[required]");
        $("#form-announce-fast #category").addClass("validate[required]");
        $("#form-announce-fast #action").addClass("validate[required]");
        $("#form-announce-fast #caption").addClass("validate[required, minSize[5], maxSize[90]]");
        $("#form-announce-fast #textview").addClass("validate[required, minSize[5], maxSize[800]]");
        $("#form-announce-fast #textindex").addClass("validate[required, minSize[5], maxSize[100]]");
        $("#form-announce-fast #cost").addClass("validate[custom[number]]");
        $("#form-announce-fast").validationEngine();
    }
    // form-complaint
    var form = $("#form-complaint");
    if (form.size() > 0) {
        $("#form-complaint #email").addClass("validate[required, minSize[4], custom[email]]");
        $("#form-complaint #captcha").addClass("validate[required, ajax[ajaxCaptcha]]");
        $("#form-complaint").validationEngine();
    }
    // form-support
    var form = $("#form-support");
    if (form.size() > 0) {
        $("#form-support #email").addClass("validate[required, minSize[4], custom[email]]");
        $("#form-support #captcha").addClass("validate[required, ajax[ajaxCaptcha]]");
        $("#form-support").validationEngine();
    }
    //form-comment
    var form = $("#form-comment");
    if (form.size() > 0) {
        $("#form-comment #textdata").addClass("validate[required, minSize[10]]");
        $("#form-comment").validationEngine();
    }
    //form-comment
    var form = $("#form-banner");
    if (form.size() > 0) {
        $("#form-banner #caption").addClass("validate[required, minSize[3]]");
        $("#form-banner #linkurl").addClass("validate[required, custom[url]]");
        $("#form-banner #captcha").addClass("validate[required, ajax[ajaxCaptcha]]");
        $("#form-banner").validationEngine();
    }
    CreateFormTrade();


    //todo
    $("[title]").tooltip({
            position: "left center",
            //offset: [-35, 0]
    });

    $('#fl_text').autocomplete({
        source: '/?ajax=autocomp',
        minLength: 3,
        delay: 200
    }).click(function(){this.select()});

    $("input[type='submit'], input[type='reset'], .link-button").button();

    $("#treeview").treeview();

    $("#datepicker").datepicker({
        altFormat: "dd-mm-yyyy"
    });

    defkit.initSelect();
});

function ContactInit()
{
    $(".contacts .root").append('<a href="javascript:;" onclick="return ContactAdd(this);">Добавить контакт</a>');
}

function ContactAdd(oObj)
{
    var container = $(oObj).prev();
    var contact = container.clone();
    $(contact).find("input:not(#mob)").val("");
    var removeLink = "<a href='javascript:;' onclick='return ContactDrop(this);'>Убрать</a>";
    $(removeLink).insertAfter(contact.appendTo(container.parent().parent()).wrap("<div></div>"));

    contact.focus();

    return false;
}

function ContactDrop(oObj)
{
    $(oObj).parent().remove();

    return false;
}

function CtxMenuShow(oObject)
{
    $(oObject).children("div").addClass('popup-showed');
    $(oObject).addClass("popup-selected");
}

function CtxMenuHide(oObject)
{
    $(oObject).children("div").removeClass('popup-showed');
    $(oObject).removeClass("popup-selected");
}


function InputCheckAll()
{
    $('input[class=checkbox-multi]').each(function() {
        this.checked = true;
    });
    return false;
}

function SilentDialog(link, initialwidth, initialtitle)
{
    if (initialwidth == null) initialwidth = 200;
    if (initialtitle == null) initialtitle = "Dialog";
    defkit.barShow();

    $("#modalwindow").html("").attr("style", "").attr("class", "")
    .load(link.href + '&aj', function() {
        /*todo ref*/
        $("input[type='submit'], input[type='reset'], .link-button").button();
        CreateCaptcha();
        CreateFormTrade();
        $("img.lazy").lazyload();
        defkit.barHide();
    }).dialog({
        position: ["center"],
        closeText: 'hide',
        title: initialtitle,
        modal: true,
        resizable: false,
        width: initialwidth
    });

    return false;
}

function DialogNative(windowID, url, initialwidth, initialheight, initialtitle)
{
    if (initialwidth == null)  initialwidth = 200;
    if (initialheight == null) initialheight = "auto";
    if (initialtitle == null)  initialtitle = "dialog";
    defkit.barShow();

    $(windowID).empty().load(url + "&aj", function() {
        $("input[type='submit'], input[type='reset'], .link-button").button();
        defkit.barHide();
    }).dialog({
        closeText: 'hide',
        title: initialtitle,
        resizable: false,
        width: initialwidth,
        height: initialheight
    });

    return false;
}

function DialogSilent(url, initialwidth, initialheight, initialtitle)
{
    return DialogNative("#modalwindow", url, initialwidth, initialheight, initialtitle)
}

function ExpandGroupMain(oObj, Index)
{
    var block = $("#block" + Index);

    if (!block.hasClass("visible")) {
        block.children("span").each(function () {
            $(this).html($(this).html().replace(/>,/g, "><br/>"));
        });
        oObj.innerHTML = "скрыть рубрики";
    } else {
        block.children("span").each(function () {
            $(this).html($(this).html().replace(/><br>/g, ">,"));
        });
        oObj.innerHTML = "все рубрики";
    }
    $("#hidden" + Index).toggleClass("visible");
    block.toggleClass("visible").toggleClass("counthide");

    return false;
}




function ajaxRequestByForm(form, url, callback, params)
{
    defkit.barShow();
    $.post(url + "&" + $(form).serialize() + "&aj", [], function (request) {
        defkit.barHide();
        if (callback) callback(request, params);
    });
    return false;
}

function ajaxRequest(url, callback, params)
{
    defkit.barShow();
    $.post(url + "&aj", [], function (request) {
        defkit.barHide();
        if (callback) callback(request, params);
    });
    return false;
}

function setValueBySelect(select, destination)
{
    document.getElementById(destination).innerHTML = select.value;
}

function setSheetVisible(sheet, owner)
{
    if (owner.checked) $(sheet).show(); else $(sheet).hide();
}


function TimerRedirect(url, container, time)
{
    var timer = setInterval(function()
    {
        time = (time == undefined) ? 3000 : time - 1000;
        if (time == 0) {
            clearTimeout(timer);
            document.location = url;
            return false;
        }
        container.innerHTML = (time / 1000);
    }, 1000);
}




$.fn.gmap = function(options)
{
    var container = this[0];
    var map;
    var coord;
    var marker;


    console.log(options);

    var options = jQuery.extend({
        draggable: true,
        settable: (options == null) || (options.Xa == null),
        Xa: 48.280274966535806,
        Ya: 68.96266626983642,
        Zm: 5,
        btn_mark: "gmap_mark"
    }, options);

    function savePosition()
    {
        coord = marker.getPosition();
        coord.Zm = map.getZoom();
        document.getElementById(options.btn_mark + "_value").setAttribute("value", JSON.stringify(coord));
    }

    function initMarker()
    {
        marker = new google.maps.Marker({
            position: coord,
            draggable: options.draggable
        });
        marker.setMap(map);

        if (options.draggable)
        {
            button_mark = document.getElementById(options.btn_mark);
            map.controls[google.maps.ControlPosition.TOP_LEFT].push(button_mark);

            google.maps.event.addListener(marker, "dragend", function() {
                savePosition();
            });

            google.maps.event.addListener(map, "zoom_changed", function() {
                savePosition();
            });

            button_mark.addEventListener("click", function() {
                marker.setPosition(map.getCenter());
                savePosition();
            });
        }
    }

    function initMap(container)
    {
        var mapOptions = {
            center: coord,
            zoom: coord.Zm,
            mapTypeId: google.maps.MapTypeId.ROADMAP,
            mapTypeControl: false,
            streetViewControl: false
        };
        map = new google.maps.Map(container, mapOptions);
        initMarker();
    }

    if ((options.draggable) && (options.settable) && (navigator.geolocation)) {
        navigator.geolocation.getCurrentPosition(function(position)
        {
            coord = new google.maps.LatLng(
                position.coords.latitude,
                position.coords.longitude);
            coord.Zm = 13;
            initMap(container);
        });
    } else {
        coord = new google.maps.LatLng(options.Xa, options.Ya);
        coord.Zm = options.Zm;
        initMap(container);
    }
};


jQuery(function($){
	$.datepicker.regional['ru'] = {
		closeText: 'Закрыть',
		prevText: '&#x3c;Пред',
		nextText: 'След&#x3e;',
		currentText: 'Сегодня',
		monthNames: ['Январь','Февраль','Март','Апрель','Май','Июнь',
		'Июль','Август','Сентябрь','Октябрь','Ноябрь','Декабрь'],
		monthNamesShort: ['Янв','Фев','Мар','Апр','Май','Июн',
		'Июл','Авг','Сен','Окт','Ноя','Дек'],
		dayNames: ['воскресенье','понедельник','вторник','среда','четверг','пятница','суббота'],
		dayNamesShort: ['вск','пнд','втр','срд','чтв','птн','сбт'],
		dayNamesMin: ['Вс','Пн','Вт','Ср','Чт','Пт','Сб'],
		weekHeader: 'Не',
		dateFormat: 'dd.mm.yy',
		firstDay: 1,
		isRTL: false,
		showMonthAfterYear: false,
		yearSuffix: ''};
	$.datepicker.setDefaults($.datepicker.regional['ru']);
});
