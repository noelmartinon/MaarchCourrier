import { Component, OnInit, AfterViewInit, Input } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { TranslateService } from '@ngx-translate/core';
import { AppService } from '@service/app.service';
import { DashboardService } from '@appRoot/home/dashboard/dashboard.service';
import { Router } from '@angular/router';

@Component({
    selector: 'app-tile-view-chart',
    templateUrl: 'tile-view-chart.component.html',
    styleUrls: ['tile-view-chart.component.scss'],
})
export class TileViewChartComponent implements OnInit, AfterViewInit {

    @Input() icon: string = '';
    @Input() resources: any[];
    @Input() route: string = null;
    @Input() tile: any;


    constructor(
        private router: Router,
        public translate: TranslateService,
        public http: HttpClient,
        public appService: AppService,
        private dashboardService: DashboardService,
    ) { }

    ngOnInit(): void { }

    ngAfterViewInit(): void { }

    goTo() {
        const data = { ...this.tile.parameters, ...this.tile };
        delete data.parameters;
        const link = this.dashboardService.getFormatedRoute(this.route, data);
        if (link) {
            this.router.navigate([link]);
        }
    }
}
