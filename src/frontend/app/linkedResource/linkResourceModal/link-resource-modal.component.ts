import { Component, Inject, ViewChild } from '@angular/core';
import { MAT_DIALOG_DATA, MatDialogRef } from '@angular/material/dialog';
import { LANG } from '../../translate.component';
import { HttpClient } from '@angular/common/http';
import { SearchAdvListComponent } from '../../adv-search/list/search-adv-list.component';
import { NotificationService } from '../../notification.service';
import { of } from 'rxjs';
import { catchError, tap } from 'rxjs/operators';

@Component({
    templateUrl: 'link-resource-modal.component.html',
    styleUrls: ['link-resource-modal.component.scss'],
})
export class LinkResourceModalComponent {
    lang: any = LANG;

    searchUrl: string = '';

    @ViewChild('appSearchAdvList', { static: false }) appSearchAdvList: SearchAdvListComponent;

    constructor(
        public http: HttpClient,
        private notify: NotificationService,
        @Inject(MAT_DIALOG_DATA) public data: any,
        public dialogRef: MatDialogRef<LinkResourceModalComponent>) {
    }

    ngOnInit(): void { }

    launchSearch(value: any) {
        this.searchUrl = value;
        this.appSearchAdvList.refreshDao(value);
    }

    linkResources() {
        const selectedRes = this.appSearchAdvList.getSelectedRessources().filter(res => res !== this.data.resId);
        this.http.post(`../rest/resources/${this.data.resId}/linkedResources`, { linkedResources: selectedRes }).pipe(
            tap(() => {
                this.dialogRef.close('success');
            }),
            catchError((err: any) => {
                this.notify.handleSoftErrors(err)
                return of(false);
            })
        ).subscribe();

    }
}
