import { ChangeDetectorRef, Component, OnInit, ViewChild, QueryList, ViewChildren } from '@angular/core';
import { MediaMatcher } from '@angular/cdk/layout';
import { HttpClient } from '@angular/common/http';
import { LANG } from '../translate.component';
import { MatDialog, MatSidenav, MatExpansionPanel, MatTableDataSource } from '@angular/material';
import { NotificationService } from '../notification.service';
import { HeaderService }        from '../../service/header.service';


import { AutoCompletePlugin } from '../../plugins/autocomplete.plugin';
import { DomSanitizer, SafeHtml } from '@angular/platform-browser';

declare function $j(selector: any): any;

declare var angularGlobals: any;

@Component({
    templateUrl: "home.component.html",
    styleUrls: ['home.component.scss'],
    providers: [NotificationService]
})
export class HomeComponent extends AutoCompletePlugin implements OnInit {

    private _mobileQueryListener    : () => void;
    mobileQuery                     : MediaQueryList;
    mobileMode                      : boolean   = false;

    coreUrl             : string;
    lang                : any       = LANG;
    loading             : boolean   = false;

    thumbnailUrl        : string;
    docUrl              : string    = '';
    homeData            : any;
    homeMessage         : string;
    dataSource          : any;
    currentDate         : string    = "";
    nbMpDocs            : number    = 0;


    public innerHtml    : SafeHtml;
    displayedColumns    : string[] = ['res_id', 'subject', 'creation_date'];

    @ViewChild('snav') snav: MatSidenav;
    @ViewChild('snav2') sidenavRight: MatSidenav;
    @ViewChildren(MatExpansionPanel) viewPanels: QueryList<MatExpansionPanel>;

    constructor(changeDetectorRef: ChangeDetectorRef, media: MediaMatcher, public http: HttpClient, public dialog: MatDialog, private sanitizer: DomSanitizer, private notify: NotificationService, private headerService: HeaderService) {
        super(http, ['users']);
        this.mobileMode = angularGlobals.mobileMode;
        $j("link[href='merged_css.php']").remove();
        this.mobileQuery = media.matchMedia('(max-width: 768px)');
        this._mobileQueryListener = () => changeDetectorRef.detectChanges();
        this.mobileQuery.addListener(this._mobileQueryListener);
    }

    ngOnInit(): void {
        this.loading = true;
        if (this.mobileMode) {
            this.displayedColumns = ['res_id', 'subject'];
        }
        this.headerService.setHeader(this.lang.home);
        window['MainHeaderComponent'].setSnav(this.snav);
        window['MainHeaderComponent'].setSnavRight(null);

        this.coreUrl = angularGlobals.coreUrl;
        let event = new Date();
        let options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };

        this.currentDate = event.toLocaleDateString('fr-FR', options);

        this.http.get(this.coreUrl + "rest/home")
            .subscribe((data: any) => {
                this.homeData = data;
                this.homeMessage = data['homeMessage'];
        });
    }

    ngAfterViewInit(): void {
        this.http.get(this.coreUrl + "rest/home/lastRessources")
        .subscribe((data: any) => {
            setTimeout(() => {
                this.dataSource = new MatTableDataSource(data.lastResources);
                this.loading = false;
            }, 0);
        });
    }

    viewDocument(row: any) {
        window.open(this.coreUrl+"rest/res/" + row.res_id + "/content", "_blank");
    }

    viewThumbnail(row:any) {
        this.thumbnailUrl = this.coreUrl+'rest/res/' + row.res_id + '/thumbnail';
        $j('#viewThumbnail').show();
        $j('#listContent').css({"overflow":"hidden"});
    }

    closeThumbnail() {
        $j('#viewThumbnail').hide();
        $j('#listContent').css({"overflow":"auto"});
    }

    goToDetail(row:any) {
        this.http.get(this.coreUrl + "rest/resources/" + row.res_id + "/isAllowed")
            .subscribe((data: any) => {
                if (data['isAllowed']) {
                    location.href = "index.php?page=details&dir=indexing_searching&id=" + row.res_id;
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
