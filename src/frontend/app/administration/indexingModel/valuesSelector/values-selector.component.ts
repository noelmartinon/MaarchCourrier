import { Component, Inject, OnInit } from '@angular/core';
import { MAT_DIALOG_DATA, MatDialogRef } from '@angular/material/dialog';
import { TranslateService } from '@ngx-translate/core';
import { HttpClient } from '@angular/common/http';
import { FunctionsService } from '@service/functions.service';

@Component({
    templateUrl: 'values-selector.component.html',
    styleUrls: ['values-selector.component.scss']
})
export class IndexingModelValuesSelectorComponent implements OnInit {

    loading: boolean = true;

    constructor(
        public translate: TranslateService,
        @Inject(MAT_DIALOG_DATA) public data: any,
        public dialogRef: MatDialogRef<IndexingModelValuesSelectorComponent>,
        public http: HttpClient,
        public functionsServce: FunctionsService
    ) { }

    ngOnInit() {
        this.loading = false;
    }

    onSubmit() {
        this.dialogRef.close(this.data.values);
    }

    allChecked() {
        return this.data.values.filter((val: any) => !val.isTitle && !val.disabled).length === this.data.values.filter((val: any) => !val.isTitle).length;
    }

    emptyChecked() {
        return this.data.values.filter((val: any) => !val.isTitle && !val.disabled).length === 0;
    }

    toggleAll(state: boolean) {
        this.data.values.filter((item: any) => !item.isTitle).forEach((item: any) => {
            item.disabled = !state;
        });
    }
}
