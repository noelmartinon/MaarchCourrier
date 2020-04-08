import { Component, OnInit, Inject, ViewChild } from '@angular/core';
import { LANG } from '../../../translate.component';
import { MAT_DIALOG_DATA, MatDialogRef } from '@angular/material/dialog';
import { take } from 'rxjs/internal/operators/take';
import { tap } from 'rxjs/internal/operators/tap';
import { EcplOnlyofficeViewerComponent } from '../../../../plugins/onlyoffice-api-js/onlyoffice-viewer.component';

@Component({
    templateUrl: 'template-file-editor-modal.component.html',
    styleUrls: ['template-file-editor-modal.component.scss'],
})
export class TemplateFileEditorModalComponent implements OnInit {

    lang: any = LANG;
    loading: boolean = false;
    editorOptions: any = null;
    file: any = null;

    @ViewChild('onlyofficeViewer', { static: true }) onlyofficeViewer: EcplOnlyofficeViewerComponent;

    constructor(public dialogRef: MatDialogRef<TemplateFileEditorModalComponent>, @Inject(MAT_DIALOG_DATA) public data: any) { }

    ngOnInit(): void {
        this.editorOptions = this.data.editorOptions;
        this.file = this.data.file;
    }

    close() {
        this.onlyofficeViewer.getFile().pipe(
            take(1),
            tap((data: any) => {
                this.dialogRef.close(data);
            })
        ).subscribe();
    }
}
