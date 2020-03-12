import { Component, OnInit } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { LANG } from '../../translate.component';
import { NotificationService } from '../../notification.service';
import { HeaderService } from '../../../service/header.service';
import { AppService } from '../../../service/app.service';

declare function $j(selector: any): any;

@Component({
    templateUrl: "reports-administration.component.html",
    providers: [AppService]
})
export class ReportsAdministrationComponent implements OnInit {

    lang: any = LANG;

    groups: any[] = [];
    reports: any[] = [];
    selectedGroup: string = "";

    loading: boolean = false;
    loadingOptions: boolean = false;

    constructor(
        public http: HttpClient, 
        private notify: NotificationService, 
        private headerService: HeaderService,
        public appService: AppService
    ) {
        $j("link[href='merged_css.php']").remove();
    }

    ngOnInit(): void {
        this.headerService.setHeader(this.lang.administration + ' ' + this.lang.reports);
        
        this.loading = true;

        this.http.get('../../rest/reports/groups')
            .subscribe((data: any) => {
                this.groups = data['groups'];
                this.loadReports(0);
                this.loading = false;
            }, () => {
                location.href = "index.php";
            });
    }

    loadReports(index: any) {
        this.selectedGroup = this.groups[index].group_id;
        this.loadingOptions = true;
        this.http.get('../../rest/reports/groups/' + this.groups[index].group_id)
            .subscribe((data: any) => {
                this.reports = data['reports'];
                this.loadingOptions = false;
            }, (err) => {
                this.notify.error(err.error.errors);
            });
    }

    saveReport() {
        this.http.put('../../rest/reports/groups/' + this.selectedGroup, this.reports)
            .subscribe(() => {
                this.notify.success(this.lang.modificationSaved);
            }, (err) => {
                this.notify.error(err.error.errors);
            });
    }
}