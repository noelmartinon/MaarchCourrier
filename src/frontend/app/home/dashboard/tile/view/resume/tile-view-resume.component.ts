import { Component, OnInit, AfterViewInit, Input } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { TranslateService } from '@ngx-translate/core';
import { AppService } from '@service/app.service';

@Component({
    selector: 'app-tile-view-resume',
    templateUrl: 'tile-view-resume.component.html',
    styleUrls: ['tile-view-resume.component.scss'],
})
export class TileViewResumeComponent implements OnInit, AfterViewInit {

    @Input() countResources: any[];
    @Input() icon: string = '';
    @Input() resourceLabel: string = '';
    @Input() route: string = null;

    constructor(
        public translate: TranslateService,
        public http: HttpClient,
        public appService: AppService,
    ) { }

    ngOnInit(): void { }

    ngAfterViewInit(): void { }
}
