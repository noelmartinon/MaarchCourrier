import { Component, OnInit, AfterViewInit, Input } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { TranslateService } from '@ngx-translate/core';
import { AppService } from '@service/app.service';
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

    constructor(
        public translate: TranslateService,
        public http: HttpClient,
        public appService: AppService,
        private router: Router,
    ) { }

    ngOnInit(): void {
        console.log(this.resources);
    }

    ngAfterViewInit(): void { }

    goTo(resource: any) {
        // TO DO format route
        const formatedRoute = this.route.replace(':resId', resource.resId);
        console.log(formatedRoute);
        // this.router.navigate([formatedRoute]);
    }
}
