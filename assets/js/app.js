/// <reference path="C:\xampp\htdocs\training\typings\tsd.d.ts" />

angular.module("mainApp", ['ngRoute']).config(function($routeProvider) {

    var version = "0.5.3";
    $routeProvider.when("/main", {
        templateUrl: "views/main.html?v=" + version
    }).
    when("/training/:id", {
        templateUrl: "views/training.html?v=" + version
    }).otherwise({ redirectTo: '/main' });

}).run(function($rootScope, $location) {

    $rootScope.pathTraining = function(id) {
        $location.path("/training/" + id);
    }

    $rootScope.tableModel = {
        count: 0
    };

    $rootScope.intervals = {
        data: 0,
        coord: 0
    }
}).
controller("mainController", function($scope, $rootScope, $interval) {
    var model = {
        id: 0,
        name: '',
        active: false
    };

    $interval.cancel($rootScope.intervals.data);
    $interval.cancel($rootScope.intervals.coord);

    $scope.add = function() {
        $.post("client.php", { action: "create_training", name: "Тренировка с Мака", date: new Date().toPHPString() }, function(data) {
            alert("ОК")
        }, "json");
    }

    $scope.date = function(val) {

        if (val == undefined) {
            return "";
        }
        var data = val.split(" ");
        var date = data[0].split("-");

        return date[2] + "." + date[1] + "." + date[0];

    }

    $scope.countArchive = 1;

    $scope.model = model;

    $.get("client.php", { action: "active_training" }, function(data) {

        if (data === "") {
            $scope.model.id = 0;
        } else {
            $scope.model = data;
        }

        $scope.$apply();
    }, "json");

    $scope.archive = [];

    if ($rootScope.tableModel.count == 0) {
        $rootScope.tableModel.count = 1;
    }
    var table = {
        begin: new Date().begin().toAppString(),
        end: new Date().end().toAppString()
    };

    $scope.getArchive = function(count) {
        $rootScope.tableModel.count = count;
        $scope.archive = [];

        $.ajax({
            url: "client.php",
            data: {
                action: "trainings",
                count: count,
                begin: table.begin + " 00:00:00",
                end: table.end + " 23:59:59"
            },
            success: function(data) {
                if (Array.isArray(data)) {
                    $scope.archive = data;
                } else {
                    $scope.archive = [data];
                }
                $scope.$apply();
            },
            error: function(data) {
                var a = 1;
            },
            dataType: "json"
        });
    }

    $scope.getArchive($rootScope.tableModel.count);

    $scope.countArchiveArray = [];

    function count() {
        $.get("client.php", {
            action: "count",
            begin: table.begin + " 00:00:00",
            end: table.end + " 23:59:59"
        }, function(data) {

            var r = Math.round(parseInt(data.count) / 10);
            if (r < parseInt(data.count) / 10) {
                r++;
            }

            $scope.countArchive = r;
            $scope.countArchiveArray = [];
            for (var i = 1; i <= $scope.countArchive; i++) {
                $scope.countArchiveArray.push(i);
            }
            $scope.$apply();

        }, "json");
    }

    count();
    $scope.table = table;
    $(function() {
        var opt = {
            weekStart: 1,
            format: "dd.mm.yyyy",
            autoclose: true,
            language: "ru",
            pickTime: false,
            minView: 2
        };
        $('#dateStart,#dateEnd')
            .datetimepicker(opt);
        $scope.$apply();

    });

    $scope.$watch("table.begin", function() {
        $scope.getArchive(1);
        count();
    });
    $scope.$watch("table.end", function() {
        $scope.getArchive(1);
        count();
    });
}).
controller("trainingController", function($scope, $routeParams, $interval, $rootScope) {



    ymaps.ready(init);
    var myMap;

    var myPlacemark = null;
    var myLine = null;

    $scope.centerMap = true;

    var coord = {
        latitude: null,
        longitude: null
    }

    var geometry = [];

    //получение координат
    function coords() {
        $.get("client.php", {
            action: "get_coords",
            id: $routeParams.id,
            skip: geometry.length
        }, function(data) {

            for (var i = 0; i < data.length; i++) {
                var item = data[i];
                geometry.push([item.latitude, item.longitude]);

                coord.latitude = item.latitude;
                coord.longitude = item.longitude;
            }
            myLine.geometry.setCoordinates(geometry);
            setCenter();

        }, "json");
    }

    function setCenter() {

        //центрировать карту
        if ($scope.centerMap) {
            myMap.setCenter([coord.latitude, coord.longitude]);
            centerInit = true;
        }

        //установка координат
        myPlacemark.geometry.setCoordinates([coord.latitude, coord.longitude]);
    }

    function init() {
        var params = { center: [] };

        if (coord.latitude != null) {
            params.center = [coord.latitude, coord.longitude];
        } else {
            params.center = [51.66764500344967, 39.222555342974246];
        }

        myMap = new ymaps.Map('map', {
            center: params.center,
            zoom: 15
        });

        myPlacemark = new ymaps.Placemark(params.center, {
            hintContent: "Максим",
            balloonContent: "Максим"
        }, {
            // Опции.
            // Необходимо указать данный тип макета.
            iconLayout: 'default#image',
            // Своё изображение иконки метки.
            //iconImageHref: 'images/bike.png',
            // Размеры метки.
            // iconImageSize: [32, 32],
            // Смещение левого верхнего угла иконки относительно
            // её "ножки" (точки привязки).
            //iconImageOffset: [-15, -4]
        });

        var properties = {
            hintContent: "Трек"
        };
        var options = {
            draggable: false,
            strokeColor: '#ff0000',
            strokeWidth: 3
        };
        myLine = new ymaps.Polyline([params.center, params.center], properties, options);
        myMap.geoObjects.add(myPlacemark);
        myMap.geoObjects.add(myLine);

        coords();
        $rootScope.intervals.coord = $interval(function() { coords() }, 3000);

    }

    $scope.time = function(val) {
        var data = val.split(" ");
        return data[1];
    }

    var trainingModel = {
        name: "",
        date: "",
        dateEnd: "",
        dist: "",
        dataReceived: "",
        speed: "",
        active: true,
        time: "",
        avgPace: "",
        avgSpeed: 0,
        type: ""
    };

    var convertTime = function(value) {

        var sec = value.toFixed(0);
        var min = sec / 60;
        min = min - (min % 1);

        sec -= min * 60;

        var h = 0;
        if (min >= 60) {
            h = min / 60;
            h = h - (h % 1)
        }

        function format(value) {
            return ("" + value).length == 1 ? "0" + value : value;
        }

        return format(min) + ":" + format(sec);
    };

    $scope.trainingModel = trainingModel;

    function get() {
        $.get("client.php", {
            action: "training_info",
            id: $routeParams.id
        }, function(data) {


            $scope.trainingModel = data;
            $scope.trainingModel.time = convertTime(data.time / 1);

            //if (data.active == false) {
            //      $scope.centerMap = false;
            //    }
            $scope.$apply();
        }, "json");
    }

    get();

    $rootScope.intervals.data = $interval(function() {
        get();

    }, 3000);

    $(function() {
        $('#autocenter').switchable({
            click: function(ev, checked) {
                $scope.centerMap = !$scope.centerMap;
            }
        });
    });

});

Date.prototype.toPHPString = function() {

    return z(this.getDate()) + "." + z(this.getMonth() + 1) + "." + this.getFullYear() + " " +
        z(this.getHours()) + ":" + z(this.getMinutes()) + ":" + z(this.getSeconds());
}

function z(val) {
    val = "" + val;

    return (val.length === 1 ? "0" : "") + val;
}

Date.prototype.toAppString = function() {

    return z(this.getDate()) + "." + z(this.getMonth() + 1) + "." + this.getFullYear();
}
Date.prototype.begin = function() {

    return new Date(this.getFullYear(), this.getMonth(), 1);
}
Date.prototype.end = function() {
    return new Date(this.getFullYear(), this.getMonth() + 1, 0)
}