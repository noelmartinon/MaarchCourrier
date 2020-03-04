import {
    AfterViewInit, ChangeDetectorRef,
    Component, ElementRef, EventEmitter, Input, OnDestroy, OnInit, QueryList,
    ViewChild,
    Renderer2,
    Output
} from '@angular/core';
import { ControlValueAccessor, FormControl } from '@angular/forms';
import { MatOption, MatSelect } from '@angular/material';
import { take, takeUntil, startWith, map } from 'rxjs/operators';
import { Subject, ReplaySubject, Observable } from 'rxjs';
import { LatinisePipe } from 'ngx-pipes';
import { LANG } from '../../app/translate.component';
import { AppService } from '../../service/app.service';

@Component({
    selector: 'plugin-select-search',
    templateUrl: 'select-search.component.html',
    styleUrls: ['select-search.component.scss', '../../app/indexation/indexing-form/indexing-form.component.scss']
})
export class PluginSelectSearchComponent implements OnInit, OnDestroy, AfterViewInit, ControlValueAccessor {
    lang: any = LANG;
    /** Label of the search placeholder */
    @Input() placeholderLabel = this.lang.chooseValue;

    @Input('formControlSelect') formControlSelect: FormControl = new FormControl();

    @Input('datas') datas: any = [];

    @Input('returnValue') returnValue: 'id' | 'object' = 'id';

    @Input('label') label: string;

    @Input('showResetOption') showResetOption: boolean;

    @Input('showLabel') showLabel: boolean = false;

    @Input('required') required: boolean = false;

    @Input('hideErrorDesc') hideErrorDesc: boolean = true;

    /**
     * ex : {class:'fa-circle', color:'#fffff', title: 'foo'}
     */
    @Input('suffixIcon') suffixIcon: any = null;

    @Input('class') class: string = "input-form";

    /**
     * Catch external event after select an element in autocomplete
     */
    @Output('afterSelected') afterSelected = new EventEmitter();
    @Output('afterOpened') afterOpened = new EventEmitter();

    /** Reference to the search input field */
    @ViewChild('searchSelectInput', { read: ElementRef, static: true }) searchSelectInput: ElementRef;

    @ViewChild('test', { static: true }) matSelect: MatSelect;

    /** Current search value */
    get value(): string {
        return this._value;
    }
    private _value: string;

    onChange: Function = (_: any) => { };
    onTouched: Function = (_: any) => { };

    public filteredDatas: Observable<string[]>;

    public filteredDatasMulti: ReplaySubject<any[]> = new ReplaySubject<any[]>(1);

    /** Reference to the MatSelect options */
    public _options: QueryList<MatOption>;

    /** Previously selected values when using <mat-select [multiple]="true">*/
    private previousSelectedValues: any[];

    /** Whether the backdrop class has been set */
    private overlayClassSet = false;

    /** Event that emits when the current value changes */
    private change = new EventEmitter<string>();

    /** Subject that emits when the component has been destroyed. */
    private _onDestroy = new Subject<void>();

    formControlSearch = new FormControl();


    constructor(private latinisePipe: LatinisePipe, private changeDetectorRef: ChangeDetectorRef, private renderer: Renderer2, public appService: AppService) {


    }

    ngOnInit() {
        // set custom panel class
        const panelClass = 'mat-select-search-panel';
        if (this.matSelect.panelClass) {
            if (Array.isArray(this.matSelect.panelClass)) {
                this.matSelect.panelClass.push(panelClass);
            } else if (typeof this.matSelect.panelClass === 'string') {
                this.matSelect.panelClass = [this.matSelect.panelClass, panelClass];
            } else if (typeof this.matSelect.panelClass === 'object') {
                this.matSelect.panelClass[panelClass] = true;
            }
        } else {
            this.matSelect.panelClass = panelClass;
        }

        // when the select dropdown panel is opened or closed
        this.matSelect.openedChange
            .pipe(takeUntil(this._onDestroy))
            .subscribe((opened) => {
                if (opened) {
                    // focus the search field when opening
                    if(!this.appService.getViewMode()) {
                        this._focus();
                    }
                } else {
                    // clear it when closing
                    //this._reset();
                    this.formControlSearch.reset();
                }
            });

        // set the first item active after the options changed
        this.matSelect.openedChange
            .pipe(take(1))
            .pipe(takeUntil(this._onDestroy))
            .subscribe(() => {
                this._options = this.matSelect.options;
                this._options.changes
                    .pipe(takeUntil(this._onDestroy))
                    .subscribe(() => {
                        const keyManager = this.matSelect._keyManager;
                        if (keyManager && this.matSelect.panelOpen) {
                            // avoid "expression has been changed" error
                            setTimeout(() => {
                                keyManager.setFirstItemActive();
                            });
                        }
                    });
            });


        /*setTimeout(() => {
            
        }, 800);*/
        this.filteredDatas = this.formControlSearch.valueChanges
            .pipe(
                startWith(''),
                map(value => this._filter(value))
            );


        // this.initMultipleHandling();

    }

    ngOnDestroy() {
        this._onDestroy.next();
        this._onDestroy.complete();
    }

    ngAfterViewInit() {
        if (this.datas.length > 5) {
            this.setOverlayClass();
        }
    }

    /**
     * Handles the key down event with MatSelect.
     * Allows e.g. selecting with enter key, navigation with arrow keys, etc.
     * @param {KeyboardEvent} event
     * @private
     */
    _handleKeydown(event: KeyboardEvent) {
        if (event.keyCode === 32) {
            // do not propagate spaces to MatSelect, as this would select the currently active option
            event.stopPropagation();
        }

    }


    writeValue(value: string) {
        const valueChanged = value !== this._value;
        if (valueChanged) {
            this._value = value;
            this.change.emit(value);
        }
    }

    onInputChange(value: any) {
        const valueChanged = value !== this._value;
        if (valueChanged) {
            this._value = value;
            this.onChange(value);
            this.change.emit(value);
        }
    }

    onBlur(value: string) {
        this.writeValue(value);
        this.onTouched();
    }

    registerOnChange(fn: Function) {
        this.onChange = fn;
    }

    registerOnTouched(fn: Function) {
        this.onTouched = fn;
    }

    /**
     * Focuses the search input field
     * @private
     */
    public _focus() {
        // save and restore scrollTop of panel, since it will be reset by focus()
        // note: this is hacky
        const panel = this.matSelect.panel.nativeElement;
        const scrollTop = panel.scrollTop;

        // focus
        if (this.datas.length > 5) {
            this.renderer.selectRootElement('#searchSelectInput').focus();
        }
        panel.scrollTop = scrollTop;
    }

    /**
     * Resets the current search value
     * @param {boolean} focus whether to focus after resetting
     * @private
     */
    public _reset(focus?: boolean) {

        this.formControlSearch.reset();

        this.renderer.selectRootElement('#searchSelectInput').focus();

    }

    /**
     * Sets the overlay class  to correct offsetY
     * so that the selected option is at the position of the select box when opening
     */
    private setOverlayClass() {
        if (this.overlayClassSet) {
            return;
        }
        const overlayClass = 'cdk-overlay-pane-select-search';

        this.matSelect.overlayDir.attach
            .pipe(takeUntil(this._onDestroy))
            .subscribe(() => {
                // note: this is hacky, but currently there is no better way to do this
                if (this.searchSelectInput !== undefined) {
                    this.searchSelectInput.nativeElement.parentElement.parentElement
                        .parentElement.parentElement.parentElement.classList.add(overlayClass);
                }
            });

        this.overlayClassSet = true;
    }


    /**
     * Initializes handling <mat-select [multiple]="true">
     * Note: to improve this code, mat-select should be extended to allow disabling resetting the selection while filtering.
     */
    private initMultipleHandling() {
        // if <mat-select [multiple]="true">
        // store previously selected values and restore them when they are deselected
        // because the option is not available while we are currently filtering
        this.matSelect.valueChange
            .pipe(takeUntil(this._onDestroy))
            .subscribe((values) => {
                if (this.matSelect.multiple) {
                    let restoreSelectedValues = false;
                    if (this._value && this._value.length
                        && this.previousSelectedValues && Array.isArray(this.previousSelectedValues)) {
                        if (!values || !Array.isArray(values)) {
                            values = [];
                        }
                        const optionValues = this.matSelect.options.map(option => option.value);
                        this.previousSelectedValues.forEach(previousValue => {
                            if (values.indexOf(previousValue) === -1 && optionValues.indexOf(previousValue) === -1) {
                                // if a value that was selected before is deselected and not found in the options, it was deselected
                                // due to the filtering, so we restore it.
                                values.push(previousValue);
                                restoreSelectedValues = true;
                            }
                        });
                    }

                    if (restoreSelectedValues) {
                        this.matSelect._onChange(values);
                    }

                    this.previousSelectedValues = values;
                }
            });
    }

    private _filter(value: string): string[] {
        if (typeof value === 'string') {
            const filterValue = this.latinisePipe.transform(value.toLowerCase());
            return this.datas.filter((option: any) => this.latinisePipe.transform(option['label'].toLowerCase()).includes(filterValue));
        } else {
            return this.datas;
        }
    }

    launchEvent(ev: any) {
        if (this.afterSelected !== undefined) {
            this.afterSelected.emit(ev.value);
        }
    }

    getErrorMsg(error: any) {
        if (error.required !== undefined) {
            return this.lang.requiredField;
        } else if (error.pattern !== undefined || error.email !== undefined) {
            return this.lang.badFormat;
        } else {
            return 'unknow validator';
        }
    }
}
