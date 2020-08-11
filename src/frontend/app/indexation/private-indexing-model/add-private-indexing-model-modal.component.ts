import { Component, Inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { LANG } from '../../translate.component';
import { TranslateService } from '@ngx-translate/core';
import { MatDialogRef, MAT_DIALOG_DATA } from '@angular/material/dialog';
import { NotificationService } from '../../../service/notification/notification.service';
import { tap, catchError } from 'rxjs/operators';
import { of } from 'rxjs';

@Component({
    templateUrl: "add-private-indexing-model-modal.component.html",
    styleUrls: ['add-private-indexing-model-modal.component.scss'],
})
export class AddPrivateIndexingModelModalComponent {
    lang: any               = LANG;
    
    constructor(
        private translate: TranslateService,
        public http: HttpClient, 
        @Inject(MAT_DIALOG_DATA) public data: any, 
        public dialogRef: MatDialogRef<AddPrivateIndexingModelModalComponent>,
        private notify: NotificationService) {
    }

    ngOnInit(): void { }

    onSubmit() {
        this.http.post("../rest/indexingModels", this.data.indexingModel).pipe(
            tap((data: any) => {
                this.data.indexingModel.id = data.id;
                this.notify.success(this.translate.instant('lang.indexingModelAdded'));
                this.dialogRef.close(this.data);
            }),
            catchError((err: any) => {
                this.notify.handleErrors(err);
                return of(false);
            })
        ).subscribe();
    }
}
