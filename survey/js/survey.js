
var I18N = {
  Enable: "可用",
  Save: "保存",
  Submit: "提交",
  Reset: "重置"
};

function initFullImages() {
  var wfimgs = $('img[data-full-image]');
  wfimgs.click(function (e) {
    var imgobj = $(this);
    var thumbsrc = imgobj.data('thumb-image');
    var fullsrc = imgobj.data('full-image');
    var cursrc = imgobj.attr('src');
    if (!thumbsrc) {
      imgobj.data('thumb-image', cursrc);
      imgobj.attr('src', fullsrc);
    } else if (cursrc == fullsrc) {
      imgobj.attr('src', thumbsrc);
    } else {
      imgobj.attr('src', fullsrc);
    }
    //imgobj.unbind('click');
  });
}

function ratingReset(input, value) {
  var w = $(input).parent();
  if (value) {
    var selectedStar = w.find('[data-value=' + value + ']');
    selectedStar.removeClass('icon-star-empty').addClass('icon-star');
    selectedStar.prevAll('[data-value]').removeClass('icon-star-empty').addClass('icon-star');
    selectedStar.nextAll('[data-value]').removeClass('icon-star').addClass('icon-star-empty');
  } else {
    w.find('.rating-clear').hide();
    w.find('[data-value]').removeClass('icon-star').addClass('icon-star-empty');
  }
}

function selectLimit(idprefix, limit) {
  var objs = $('input[id^="' + idprefix + '"]');
  var selectCount = 0;
  for (var i = 0; i < objs.length; i++) {
    if ($(objs[i]).prop('checked')) {
      selectCount++;
    }
  }
  if (selectCount >= limit) {
    for (var i = 0; i < objs.length; i++) {
      if (!$(objs[i]).prop('checked')) {
        $(objs[i]).prop('disabled', true);
      }
    }
  } else {
    for (var i = 0; i < objs.length; i++) {
      if ($(objs[i]).prop('disabled')) {
        $(objs[i]).prop('disabled', false);
      }
    }
  }
}

function sendSurvey(id, survey, callback) {
  $.ajax({
    type: 'POST',
    url: '/survey/index.php?action=submit',
    data: {id: id, answer: JSON.stringify(survey.answer)},
    dataType: 'json',
    success: function (data) {
      if (data && data.errno === 0) {
        callback(true);
      } else {
        callback(false, data);
      }
    },
    complete: function (xhr, status) {
      if (status != 'success') {
        callback(false);
      }
    }
  });
}

function submitSurvey(s, storage) {

  var cat = 'survey';
  var id = s.id;
  var question = s.question;

  var catid = cat + '-' + id;
  var survey = {};

  //survey.enable = $('#input-' + catid + '-enable').prop('checked') ? true : false;
  survey.answer = {};
  var passed = true;
  var sid = 0;
  for (var s in question) {
    var inputId = 'input-' + catid + '-question-' + (sid++);
    var inputName = cat + '_' + id + '_question[' + s + ']';
    var value;
    switch (question[s].type) {
      case 'boolean':
        value = $('#' + inputId).prop('checked') ? true : false;
        break;
      case 'number':
      case 'string':
      case 'text':
        value = $('#' + inputId).val();
        break;
      case 'range':
        value = $('#' + inputId).val();
        break;
      case 'rating':
        value = $('#' + inputId).val();
        value = parseInt(value);
        if (!value) {
          value = 0;
        }
        break;
      case 'select':
        if (question[s].multiple) {
          value = [];
        }
        var inputs = $('input[name="' + inputName + '"]').each(function () {
          if ($(this).prop('checked')) {
            if (question[s].multiple) {
              value.push(parseInt($(this).val()));
            } else {
              value = parseInt($(this).val());
              return false;
            }
          }
        });
        break;
    }
    var controlobj = $('#' + inputId).parent();
    while (!controlobj.hasClass('control-group') && controlobj && controlobj.length > 0) {
      controlobj = controlobj.parent();
    }
    if (!value && question[s].require) {
      controlobj.addClass('error');
      passed = false;
      //break;
    } else {
      controlobj.removeClass('error');
    }
    if (value === undefined) {
      continue;
    }
    if (question[s].escape) {
      value = escape(value, cat);
    }
    survey.answer[s] = value;
  }

  if (!passed) {
    $('#alert-error-' + catid).show('fast', function () {
      var that = this;
      setTimeout(function () {
        $(that).fadeOut();
      }, 1500);
    });
    return;
  }

  if (storage) {
    if (!storage[cat]) {
      storage[cat] = {};
    }
  } else {
    storage = {};
    storage[cat] = {};
  }

  storage[cat][id] = survey;
  //gpw.update();

  sendSurvey(id, survey, function (success, err) {
    if (success) {
      $('#alert-submitted-' + catid).show('fast'); /*, function () {
        var that = this;
        setTimeout(function () {
          $(that).fadeOut();
        }, 1500);
      });*/
    } else {
      var errtipscontrol = $('#alert-error-' + catid);
      if (err && err.msg) {
        errtipscontrol.html(err.msg);
      }
      errtipscontrol.show('fast', function () {
        var that = this;
        setTimeout(function () {
          $(that).fadeOut();
        }, 3500);
      });
    }
  });

}

function resetSurvey(s) {
  var cat = 'survey';
  var id = s.id;
  var question = s.question;
  var sid = 0;

  for (var s in question) {
    var inputId = 'input-' + cat + '-' + id + '-question-' + (sid++);
    var inputName = cat + '_' + id + '_question[' + s + ']';
    var value = question[s].default;
    if (question[s].escape) {
      value = unescape(value, cat);
    }
    switch (question[s].type) {
      case 'boolean':
        $('#' + inputId).prop('checked', value);
        break;
      case 'number':
      case 'string':
      case 'text':
        $('#' + inputId).val(value);
        break;
      case 'range':
        $('#' + inputId).slider('setValue', value);
        break;
      case 'rating':
        {
          var _input = $('#' + inputId);
          _input.val(value);
          ratingReset(_input, value);
        }
        break;
      case 'select':
        if (question[s].multiple) {
          $('input[name="' + inputName + '"]').prop('checked', false);
          for (var i = 0; i < value.length; i++) {
            $('#' + inputId + '-' + value[i]).prop('checked', true);
          }
        } else {
          $('#' + inputId + '-' + value).prop('checked', true);
        }
        break;
    }
  }
}

function initSurvey(s, storage) {
  if (!s || !s.id) {
    return;
  }
  var menuitem = '';
  var tabcontent = '';

  menuitem = '';

  var cat = 'survey';
  var O = s;
  var w = s.id;

    var catid = cat + '-' + w;

    //fade
    tabcontent += '<div class="tab-pane well survey-well" id="' + catid + '">\
      <div class="survey-info">\
        <div class="alert alert-block alert-info">\
          <h4>' + htmlencode(O.title) + '</h4>\
          <p>' + O.description + '</p>\
        </div>\
      </div>\
      <form class="form-horizontal">';

    var question = O.question;
    var storage_single = {};
    var storage_answer = {};
    if (storage && storage[cat]) {
      if (storage[cat][w]) {
        storage_single = storage[cat][w];
        if (storage_single.answer) {
          storage_answer = storage_single.answer;
        }
      }
    }

    var sid = 0;
    for (var s in question) {
      var inputId = 'input-' + catid + '-question-' + (sid++);
      var inputName = cat + '_' + w + '_question[' + s + ']';
      var value = storage_answer[s] ? storage_answer[s] : question[s].default;

      if (storage_answer[s]) {
        if (question[s].escape) {
          value = unescape(value, cat);
        }
      }

      switch (question[s].type) {
        case 'string':
        case 'text':
          value = htmlencode(value);
          break;
        case 'rating':
          if (storage_answer[s] === 0) {
            value = 0;
          }
          break;
      }

      tabcontent += '<div class="control-group">\
        ' + (question[s].type !== 'boolean' ? '<label class="control-label" for="' + inputId + '">' + question[s].name + '</label>' : '') + '\
        <div class="controls">';

      switch (question[s].type) {
        case 'boolean':
          tabcontent += '\
            <label class="checkbox">\
              <input type="checkbox" id="' + inputId + '" name="' + inputName + '"' + (value ? ' checked' : '') + '> ' + question[s].name + '\
            </label>';
          break;
        case 'number':
        case 'string':
          tabcontent += '\
            <input type="text" id="' + inputId + '" ' + (question[s].type == 'string' ? ' class="span7"' : '') + 'name="' + inputName + '"' + '" placeholder="' + question[s].placeholder + '" value="' + value + '">\
            ';
          break;
        case 'text':
          tabcontent += '\
            <textarea id="' + inputId + '" name="' + inputName + '" class="span5" style="height: 300px;">' + value + '</textarea>';
          break;
        case 'range':
          tabcontent += '<input type="text" id="' + inputId + '" name="' + inputName + '" class="span2 slider" \
            value="' + value + '"\
            data-slider-min="' + question[s].range.min + '" \
            data-slider-max="' + question[s].range.max + '" \
            data-slider-step="' + question[s].range.step + '" \
            data-slider-value="' + value + '" \
            data-slider-orientation="horizontal" \
            data-slider-selection="after" \
            data-slider-tooltip="hide">';
          break;
        case 'rating':
          tabcontent += '<div class="description">' + question[s].description + '</div>';
          tabcontent += '<input type="number" id="' + inputId + '" name="' + inputName + '" class="rating" \
            value="' + value + '"\
            data-min="1" \
            data-max="' + question[s].stars + '" \
            data-clearable="true" \
            data-value="' + value + '">';
          break;
        case 'select':
          var stname = question[s].multiple ? 'checkbox' : 'radio';
          tabcontent += '';
          for (var i = 0; i < question[s].option.length; i++) {
            tabcontent += '<div class="' + stname + '">';
            if (question[s].inline) {
              tabcontent += '<label class="' + stname + '-inline">';
            } else {
              tabcontent += '<label>';
            }
            tabcontent += '<input type="' + stname + '" id="' + inputId + '-' + i + '" name="' + inputName + '" value="' + i + '"';
            var checked = false;
            if (question[s].multiple) {
              for (var j = 0; j < value.length; j++) {
                if (value[j] === i) {
                  checked = true;
                  break;
                }
              }
            } else {
              checked = (value === i);
            }
            if (checked) {
              tabcontent += ' checked';
            }
            if (question[s].limit) {
              tabcontent += ' onclick="selectLimit(\'' + inputId + '\', ' + question[s].limit + ')"';
            }
            tabcontent +=  '>' + question[s].option[i] + '</label>\
            </div>'
          }
          break;
      }
      if (question[s].help) {
        tabcontent += '<span class="help-block">' + question[s].help + '</span>';
      }
      tabcontent += '</div>\
        </div>';
    }

    tabcontent += '<div class="form-actions">\
          <div id="alert-submitted-' + catid + '" class="alert alert-success" style="display: none;">\
            Submitted, thanks\
          </div>\
          <div id="alert-error-' + catid + '" class="alert alert-error" style="display: none;">\
            Somethings wrong!\
          </div>\
          <button type="button" id="btn-submit-' + catid + '" class="btn btn-primary btn-submit">' + I18N.Submit + '</button>\
          <button type="button" id="btn-reset-' + catid + '" class="btn">' + I18N.Reset + '</button>\
        </div>\
      </form>\
    </div>';

  $('#survey').append(tabcontent).show();
  $('#loading').hide();

  $('button[id^="btn-submit-"]').click(function (e) {
    var id = $(this).attr('id');
    var subids = id.split('-');
    submitSurvey(survey, storage);
  });
  $('button[id^="btn-reset-"]').click(function (e) {
    var id = $(this).attr('id');
    var subids = id.split('-');
    resetSurvey(survey);
  });
  $('input.slider').slider();
  if ($('input.rating[type=number]').length > 0) {
    $('input.rating[type=number]').rating();
  }

  initFullImages();
}

$(document).ready(function () {
  //alert(window.survey);
  initSurvey(survey, storage);
});