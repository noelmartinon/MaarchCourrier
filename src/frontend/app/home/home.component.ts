import { Component, OnInit, QueryList, ViewChildren, AfterViewInit } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { LANG } from '../translate.component';
import { MatDialog } from '@angular/material/dialog';
import { MatExpansionPanel } from '@angular/material/expansion';
import { MatTableDataSource } from '@angular/material/table';
import { NotificationService } from '../notification.service';
import { HeaderService } from '../../service/header.service';
import { DomSanitizer, SafeHtml } from '@angular/platform-browser';
import { AppService } from '../../service/app.service';
import { Router } from '@angular/router';

declare var $: any;

@Component({
    templateUrl: 'home.component.html',
    styleUrls: ['home.component.scss'],
    providers: [AppService]
})
export class HomeComponent implements OnInit, AfterViewInit {

    lang: any = LANG;
    loading: boolean = false;

    thumbnailUrl: string;
    docUrl: string = '';
    homeData: any;
    homeMessage: string;
    dataSource: any;
    currentDate: string = '';
    nbMpDocs: number = 0;


    public innerHtml: SafeHtml;
    displayedColumns: string[] = ['res_id', 'subject', 'creation_date'];

    @ViewChildren(MatExpansionPanel) viewPanels: QueryList<MatExpansionPanel>;

    constructor(
        public http: HttpClient,
        public dialog: MatDialog,
        private sanitizer: DomSanitizer,
        private notify: NotificationService,
        private headerService: HeaderService,
        public appService: AppService,
        private router: Router
    ) {
        (<any>window).pdfWorkerSrc = '../../node_modules/pdfjs-dist/build/pdf.worker.min.js';
    }

    ngOnInit(): void {
        this.loading = true;
        if (this.appService.getViewMode()) {
            this.displayedColumns = ['res_id', 'subject'];
        }
        this.headerService.setHeader(this.lang.home);

        const event = new Date();
        const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };

        this.currentDate = event.toLocaleDateString(this.lang.langISO, options);

        this.http.get('../../rest/home')
            .subscribe((data: any) => {
                this.homeData = data;
                this.homeMessage = data['homeMessage'];
            });
    }

    ngAfterViewInit(): void {
        this.http.get('../../rest/home/lastRessources')
            .subscribe((data: any) => {
                setTimeout(() => {
                    this.dataSource = new MatTableDataSource(data.lastResources);
                    this.loading = false;
                }, 0);
            });
    }

    viewDocument(row: any) {
        this.http.get(`../../rest/resources/${row.res_id}/content?mode=view`, { responseType: 'blob' })
            .subscribe((data: any) => {
                const file = new Blob([data], { type: 'application/pdf' });
                const fileURL = URL.createObjectURL(file);
                const newWindow = window.open();
                newWindow.document.write(`<iframe style="width: 100%;height: 100%;margin: 0;padding: 0;" src="${fileURL}" frameborder="0" allowfullscreen></iframe>`);
                newWindow.document.title = row.alt_identifier;
            });
    }

    viewThumbnail(row: any) {
        const timeStamp = +new Date();
        this.thumbnailUrl = '../../rest/resources/' + row.res_id + '/thumbnail?tsp=' + timeStamp;
        $('#viewThumbnail').show();
        $('#listContent').css({ 'overflow': 'hidden' });
    }

    closeThumbnail() {
        $('#viewThumbnail').hide();
        $('#listContent').css({ 'overflow': 'auto' });
    }

    goToDetail(row: any) {
        this.http.get('../../rest/resources/' + row.res_id + '/isAllowed')
            .subscribe((data: any) => {
                if (data['isAllowed']) {
                    this.router.navigate([`/resources/${row.res_id}`]);
                } else {
                    this.notify.error(this.lang.documentOutOfPerimeter);
                }
            }, () => {
                this.notify.error(this.lang.errorOccured);
            });
    }

    updateNbMpDocs(ev: any) {
        this.nbMpDocs = ev;
    }
}
