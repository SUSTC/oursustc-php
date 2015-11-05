
function initSurveyCharts(survey) {
  var chartscount = 0;
  var colors = ['#B10000','#C56A6A'];

  $('#survey').append('<div class="tab-pane well survey-well"></div>');
  var well = $('#survey').find('.survey-well');

  well.append('<div class="alert alert-block alert-info">'
    + '<h4>' + survey.title + '</h4>'
    + '<p>总填写人数: ' + survey.count + '</p>'
    + '</div>');

  for (var key in survey.charts) {
    chartscount++;
    var chartsId = 'survey-charts-plot-' + chartscount.toString();
    well.append('<div class="survey-charts-plot" id="' + chartsId + '"></div>');

    var data = [];
    var q = survey.charts[key];
    var d = survey.data[key];

    if (d) {
      switch (q.type) {
        case 'boolean':
          break;
        case 'range':
          break;
        case 'rating':
          for (var i = 0; i <= q.stars; i++) {
            data.push([i + ' Stars', 0]);
          }
          for (var i = 0; i < d.length; i++) {
            data[parseInt(d[i])][1]++;
          }
          break;
        case 'select':
          for (var i = 0; i < q.option.length; i++) {
            data.push([q.option[i], 0]);
          }
          if (q.multiple) {
            for (var i = 0; i < d.length; i++) {
              for (var j = 0; j < d[i].length; j++) {
                data[parseInt(d[i][j])][1]++;
              }
            }
          } else {
            for (var i = 0; i < d.length; i++) {
              data[parseInt(d[i])][1]++;
            }
          }
          break;
      }
    }

    if (data.length) {
      var myChart = new JSChart(chartsId, 'bar', '');

      myChart.setDataArray(data);

      var _colors = [];
      var maxY = 0;
      for (var i = 0; i < data.length; i++) {
        _colors.push(colors[i % colors.length]);
        if (data[i][1] > maxY) {
          maxY = data[i][1];
        }
      }

      myChart.colorize(_colors);

      myChart.setAxisPaddingLeft(200);
      myChart.setSize(800, 300);
      myChart.setBarValues(false);
      myChart.setBarSpacingRatio(45);
      myChart.setBarOpacity(0.8);
      myChart.setBarBorderWidth(0);
      myChart.setTitle(q.name);
      myChart.setTitleFontSize(16);
      myChart.setTitleColor('#7F1A1A');
      myChart.setAxisValuesColor('#7F1A1A');
      myChart.setAxisNameX('');
      myChart.setAxisNameY('');
      myChart.setAxisColor('#9E2323');
      myChart.setAxisNameColor('#7F1A1A');
      myChart.setGridOpacity(0.8);
      myChart.setGridColor('#D3B5B4');
      myChart.setIntervalEndY(maxY);
      myChart.setAxisReversed(true);

      myChart.draw();
    }

    //myChart.colorize(['#B10000','#C56A6A','#B10000','#C56A6A','#B10000','#C56A6A','#B10000','#C56A6A','#B10000']);

  }

  well.append('<div class="clearfix"></div>');
  $('#survey').show();
  $('#loading').hide();
}

$(document).ready(function () {
  //alert(window.survey);
  initSurveyCharts(survey);
});