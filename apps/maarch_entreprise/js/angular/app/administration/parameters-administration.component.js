"use strict";
var __decorate = (this && this.__decorate) || function (decorators, target, key, desc) {
    var c = arguments.length, r = c < 3 ? target : desc === null ? desc = Object.getOwnPropertyDescriptor(target, key) : desc, d;
    if (typeof Reflect === "object" && typeof Reflect.decorate === "function") r = Reflect.decorate(decorators, target, key, desc);
    else for (var i = decorators.length - 1; i >= 0; i--) if (d = decorators[i]) r = (c < 3 ? d(r) : c > 3 ? d(target, key, r) : d(target, key)) || r;
    return c > 3 && r && Object.defineProperty(target, key, r), r;
};
var __metadata = (this && this.__metadata) || function (k, v) {
    if (typeof Reflect === "object" && typeof Reflect.metadata === "function") return Reflect.metadata(k, v);
};
Object.defineProperty(exports, "__esModule", { value: true });
var core_1 = require("@angular/core");
var http_1 = require("@angular/common/http");
var translate_component_1 = require("../translate.component");
var notification_service_1 = require("../notification.service");
var material_1 = require("@angular/material");
var ParametersAdministrationComponent = /** @class */ (function () {
    function ParametersAdministrationComponent(http, notify) {
        this.http = http;
        this.notify = notify;
        this.lang = translate_component_1.LANG;
        this.resultInfo = "";
        this.loading = false;
        this.data = [];
        this.displayedColumns = ['id', 'description', 'param_value_string', 'param_value_int', 'param_value_date', 'actions'];
        this.dataSource = new material_1.MatTableDataSource(this.data);
    }
    ParametersAdministrationComponent.prototype.applyFilter = function (filterValue) {
        filterValue = filterValue.trim(); // Remove whitespace
        filterValue = filterValue.toLowerCase(); // MatTableDataSource defaults to lowercase matches
        this.dataSource.filter = filterValue;
    };
    ParametersAdministrationComponent.prototype.updateBreadcrumb = function (applicationName) {
        if ($j('#ariane')[0]) {
            $j('#ariane')[0].innerHTML = "<a href='index.php?reinit=true'>" + applicationName + "</a> > <a onclick='location.hash = \"/administration\"' style='cursor: pointer'>" + this.lang.administration + "</a> > " + this.lang.parameters;
        }
    };
    ParametersAdministrationComponent.prototype.ngOnInit = function () {
        var _this = this;
        this.updateBreadcrumb(angularGlobals.applicationName);
        this.coreUrl = angularGlobals.coreUrl;
        this.http.get(this.coreUrl + 'rest/administration/parameters')
            .subscribe(function (data) {
            _this.parametersList = data.parametersList;
            _this.data = _this.parametersList;
            setTimeout(function () {
                _this.dataSource = new material_1.MatTableDataSource(_this.data);
                _this.dataSource.paginator = _this.paginator;
                _this.dataSource.sort = _this.sort;
            }, 0);
            _this.loading = false;
        });
    };
    ParametersAdministrationComponent.prototype.goUrl = function () {
        location.href = 'index.php?admin=parameters&page=control_param_technic';
    };
    ParametersAdministrationComponent.prototype.deleteParameter = function (paramId) {
        var _this = this;
        var resp = confirm(this.lang.confirmAction + ' ' + this.lang.delete + ' « ' + paramId + ' »');
        if (resp) {
            this.http.delete(this.coreUrl + 'rest/parameters/' + paramId)
                .subscribe(function (data) {
                _this.data = data.parameters;
                _this.dataSource = new material_1.MatTableDataSource(_this.data);
                _this.dataSource.paginator = _this.paginator;
                _this.dataSource.sort = _this.sort;
                _this.notify.success(_this.lang.parameterDeleted + ' « ' + paramId + ' »');
            }, function (err) {
                _this.notify.error(JSON.parse(err._body).errors);
            });
        }
    };
    __decorate([
        core_1.ViewChild(material_1.MatPaginator),
        __metadata("design:type", material_1.MatPaginator)
    ], ParametersAdministrationComponent.prototype, "paginator", void 0);
    __decorate([
        core_1.ViewChild(material_1.MatSort),
        __metadata("design:type", material_1.MatSort)
    ], ParametersAdministrationComponent.prototype, "sort", void 0);
    ParametersAdministrationComponent = __decorate([
        core_1.Component({
            templateUrl: angularGlobals["parameters-administrationView"],
            styleUrls: ['css/parameters-administration.component.css'],
            providers: [notification_service_1.NotificationService]
        }),
        __metadata("design:paramtypes", [http_1.HttpClient, notification_service_1.NotificationService])
    ], ParametersAdministrationComponent);
    return ParametersAdministrationComponent;
}());
exports.ParametersAdministrationComponent = ParametersAdministrationComponent;
