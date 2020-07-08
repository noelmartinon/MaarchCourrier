import { Component, OnInit, Inject, ViewChild } from '@angular/core';
import { LANG } from '../../../translate.component';
import { MAT_DIALOG_DATA, MatDialogRef } from '@angular/material/dialog';
import { take } from 'rxjs/internal/operators/take';
import { tap } from 'rxjs/internal/operators/tap';
import { EcplOnlyofficeViewerComponent } from '../../../../plugins/onlyoffice-api-js/onlyoffice-viewer.component';
import {CollaboraOnlineViewerComponent} from '../../../../plugins/collabora-online/collabora-online-viewer.component';

@Component({
    templateUrl: 'template-file-editor-modal.component.html',
    styleUrls: ['template-file-editor-modal.component.scss'],
})
export class TemplateFileEditorModalComponent implements OnInit {

    lang: any = LANG;
    loading: boolean = false;
    editorOptions: any = null;
    file: any = null;
    editorType: any = null;

    @ViewChild('onlyofficeViewer', { static: true }) onlyofficeViewer: EcplOnlyofficeViewerComponent;
    @ViewChild('collaboraOnlineViewer', { static: false }) collaboraOnlineViewer: CollaboraOnlineViewerComponent;

    constructor(public dialogRef: MatDialogRef<TemplateFileEditorModalComponent>, @Inject(MAT_DIALOG_DATA) public data: any) { }

    ngOnInit(): void {
        console.log(this.data);
        this.editorOptions = this.data.editorOptions;
        this.file = this.data.file;
        this.editorType = this.data.editorType;
    }

    close() {
        if (this.editorType === 'onlyoffice') {
            this.onlyofficeViewer.getFile().pipe(
                take(1),
                tap((data: any) => {
                    this.dialogRef.close(data);
                })
            ).subscribe();
        } else if (this.editorType === 'collaboraonline') {
            this.collaboraOnlineViewer.getFile().pipe(
                take(1),
                tap((data: any) => {
                    this.dialogRef.close(data);
                })
            ).subscribe();
        } else {
            this.dialogRef.close();
        }
    }
}
