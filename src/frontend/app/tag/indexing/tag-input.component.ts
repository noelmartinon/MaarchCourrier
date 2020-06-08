import { Component, OnInit, Input, ViewChild, ElementRef } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { LANG } from '../../translate.component';
import { NotificationService } from '../../notification.service';
import { HeaderService } from '../../../service/header.service';
import { MatDialog, MatDialogRef } from '@angular/material/dialog';
import { AppService } from '../../../service/app.service';
import { SortPipe } from '../../../plugins/sorting.pipe';
import { FormControl } from '@angular/forms';
import { Observable, of } from 'rxjs';
import { debounceTime, filter, distinctUntilChanged, tap, switchMap, exhaustMap, catchError, map } from 'rxjs/operators';
import { LatinisePipe } from 'ngx-pipes';
import { PrivilegeService } from '../../../service/privileges.service';
import { FunctionsService } from '../../../service/functions.service';
import { ThesaurusModalComponent } from './thesaurus/thesaurus-modal.component';

@Component({
    selector: 'app-tag-input',
    templateUrl: "tag-input.component.html",
    styleUrls: [
        'tag-input.component.scss',
        '../../indexation/indexing-form/indexing-form.component.scss'
    ],
    providers: [NotificationService, AppService, SortPipe]
})

export class TagInputComponent implements OnInit {

    lang: any = LANG;

    loading: boolean = false;

    key: string = 'idToDisplay';

    canAdd: boolean = false;

    listInfo: string;
    myControl = new FormControl();
    filteredOptions: Observable<string[]>;
    options: any;
    valuesToDisplay: any = {};
    dialogRef: MatDialogRef<any>;
    newIds: number[] = [];

    tags: any[] = [];


    /**
     * FormControl used when autocomplete is used in form and must be catched in a form control.
     */
    @Input('control') controlAutocomplete: FormControl;

    @ViewChild('autoCompleteInput', { static: true }) autoCompleteInput: ElementRef;

    constructor(
        public http: HttpClient,
        private notify: NotificationService,
        public dialog: MatDialog,
        private headerService: HeaderService,
        public appService: AppService,
        private latinisePipe: LatinisePipe,
        private privilegeService: PrivilegeService,
        private functionsService: FunctionsService
    ) {

    }

    ngOnInit() {
        this.controlAutocomplete.setValue(this.controlAutocomplete.value === null || this.controlAutocomplete.value === '' ? [] : this.controlAutocomplete.value);
        this.canAdd = this.privilegeService.hasCurrentUserPrivilege('manage_tags_application');
        this.initFormValue();
        this.initAutocompleteRoute();
    }

    initAutocompleteRoute() {
        this.listInfo = this.lang.autocompleteInfo;
        this.options = [];
        this.myControl.valueChanges
            .pipe(
                //tap((value) => this.canAdd = value.length === 0 ? false : true),
                debounceTime(300),
                filter(value => value.length > 2),
                distinctUntilChanged(),
                tap(() => this.loading = true),
                switchMap((data: any) => this.getDatas(data)),
                tap((data: any) => {
                    if (data.length === 0) {
                        this.listInfo = this.lang.noAvailableValue;
                    } else {
                        this.listInfo = '';
                    }
                    this.options = data;
                    this.filteredOptions = of(this.options);
                    this.loading = false;
                })
            ).subscribe();
    }

    getDatas(data: string) {
        return this.http.get('../rest/autocomplete/tags', { params: { "search": data } });
    }

    selectOpt(ev: any) {
        this.setFormValue(ev.option.value);
        this.myControl.setValue('');

    }

    initFormValue() {
        this.controlAutocomplete.value.forEach((ids: any) => {
            this.http.get('../rest/tags/' + ids).pipe(
                tap((data: any) => {
                    this.valuesToDisplay[data.id] = data.label;
                })
            ).subscribe();
        });
    }

    setFormValue(item: any) {
        if (this.controlAutocomplete.value.indexOf(item['id']) === -1) {
            let arrvalue = [];
            if (this.controlAutocomplete.value !== null) {
                arrvalue = this.controlAutocomplete.value;
            }
            arrvalue.push(item['id']);
            this.valuesToDisplay[item['id']] = item[this.key];
            this.controlAutocomplete.setValue(arrvalue);
        }
    }

    resetAutocomplete() {
        this.options = [];
        this.listInfo = this.lang.autocompleteInfo;
    }

    private _filter(value: string): string[] {
        if (typeof value === 'string') {
            const filterValue = this.latinisePipe.transform(value.toLowerCase());
            return this.options.filter((option: any) => this.latinisePipe.transform(option[this.key].toLowerCase()).includes(filterValue));
        } else {
            return this.options;
        }
    }

    unsetValue() {
        this.controlAutocomplete.setValue('');
        this.myControl.setValue('');
        this.myControl.enable();
    }

    removeItem(index: number) {

        if (this.newIds.indexOf(this.controlAutocomplete.value[index]) === -1) {
            let arrValue = this.controlAutocomplete.value;
            this.controlAutocomplete.value.splice(index, 1);
            this.controlAutocomplete.setValue(arrValue);
        } else {
            this.http.delete('../rest/tags/' + this.controlAutocomplete.value[index]).pipe(
                tap((data: any) => {
                    let arrValue = this.controlAutocomplete.value;
                    this.controlAutocomplete.value.splice(index, 1);
                    this.controlAutocomplete.setValue(arrValue);
                }),
                catchError((err: any) => {
                    this.notify.handleErrors(err);
                    return of(false);
                })
            ).subscribe();
        }
    }

    addItem() {
        const newElem = {};

        newElem[this.key] = this.myControl.value;

        this.http.post('../rest/tags', { label: newElem[this.key] }).pipe(
            tap((data: any) => {
                for (var key in data) {
                    newElem['id'] = data[key];
                    this.newIds.push(data[key]);
                }
                this.setFormValue(newElem);
                this.myControl.setValue('');
            }),
            catchError((err: any) => {
                this.notify.handleErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    openThesaurus(tagId: number = null) {
        
        const dialogRef = this.dialog.open(ThesaurusModalComponent, {
            panelClass: 'maarch-modal',
            width: '600px',
            data: {
                id : tagId
            }
        });
        dialogRef.afterClosed().pipe(
            filter((data: any) => !this.functionsService.empty(data)),
            map((data: any) => {
                return {
                    id : data.id,
                    idToDisplay : data.label
                };
            }),
            tap((tagItem: any) => {
                console.log(tagItem);

                this.setFormValue(tagItem);
            }),
            catchError((err: any) => {
                this.notify.handleErrors(err);
                return of(false);
            })
        ).subscribe();
    }
}