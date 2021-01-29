import { Component, OnInit, AfterViewInit, Input } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { TranslateService } from '@ngx-translate/core';
import { AppService } from '@service/app.service';
import { Router } from '@angular/router';

@Component({
    selector: 'app-tile-view-list',
    templateUrl: 'tile-view-list.component.html',
    styleUrls: ['tile-view-list.component.scss'],
})
export class TileViewListComponent implements OnInit, AfterViewInit {

    @Input() displayColumns: string[];

    @Input() resources: any[];
    @Input() icon: string = '';
    @Input() route: string = null;

    thumbnailUrl: string = '';
    testDate = new Date();

    constructor(
        public translate: TranslateService,
        public http: HttpClient,
        public appService: AppService,
        private router: Router,
    ) { }

    ngOnInit(): void { }

    ngAfterViewInit(): void { }

    viewThumbnail(ev: any, resource: any) {
        const timeStamp = +new Date();
        this.thumbnailUrl = '../rest/resources/' + resource.resId + '/thumbnail?tsp=' + timeStamp;
        $('#viewThumbnail').show();
        console.log(ev);
    }

    closeThumbnail() {
        $('#viewThumbnail').hide();
    }

    goTo(resource: any) {
        // TO DO format route
        const formatedRoute = this.route.replace(':resId', resource.resId);
        console.log(formatedRoute);
        // this.router.navigate([formatedRoute]);
    }
}
