// import { LANG } from '../app/translate.component';
import { NativeDateAdapter, DateAdapter, MAT_DATE_FORMATS, MAT_DATE_LOCALE } from "@angular/material/core";

export class AppDateAdapter extends NativeDateAdapter {
    // lang: any = LANG;
    parse(value: any): Date | null {
        if ((typeof value === 'string') && (value.indexOf('/') > -1)) {
            const str = value.split('/');
            const year = Number(str[2]);
            const month = Number(str[1]) - 1;
            const date = Number(str[0]);
            return new Date(year, month, date);
        }
        const timestamp = typeof value === 'number' ? value : Date.parse(value);
        return isNaN(timestamp) ? null : new Date(timestamp);
    }

    format(date: Date, displayFormat: Object): string {
        if (displayFormat == "input") {
            let day = date.getDate();
            let month = date.getMonth() + 1;
            let year = date.getFullYear();
            return this._to2digit(day) + '/' + this._to2digit(month) + '/' + year;
        } else {
            const options = { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' };
            return date.toLocaleDateString('fr-FR', options);
        }
    }

    private _to2digit(n: number) {
        return ('00' + n).slice(-2);
    }

    getFirstDayOfWeek(): number {
        return 1;
    }

}

export const APP_DATE_FORMATS =
    {
        parse: {
            dateInput: { month: 'short', year: 'numeric', day: 'numeric' }
        },
        display: {
            // dateInput: { month: 'short', year: 'numeric', day: 'numeric' },
            dateInput: 'input',
            monthYearLabel: { month: 'short', year: 'numeric', day: 'numeric' },
            dateA11yLabel: { year: 'numeric', month: 'long', day: 'numeric' },
            monthYearA11yLabel: { year: 'numeric', month: 'long' },
        }
    };


import { NgModule } from '@angular/core';
import { DndModule } from 'ng2-dnd';
import { MatAutocompleteModule } from '@angular/material/autocomplete';
import { MatBadgeModule } from '@angular/material/badge';
import { MatBottomSheetModule } from '@angular/material/bottom-sheet';
import { MatButtonModule } from '@angular/material/button';
import { MatButtonToggleModule } from '@angular/material/button-toggle';
import { MatCardModule } from '@angular/material/card';
import { MatCheckboxModule } from '@angular/material/checkbox';
import { MatChipsModule } from '@angular/material/chips';
import { MatNativeDateModule } from '@angular/material/core';
import { MatDatepickerModule } from '@angular/material/datepicker';
import { MatDialogActions, MatDialogModule } from '@angular/material/dialog';
import { MatExpansionModule } from '@angular/material/expansion';
import { MatGridListModule } from '@angular/material/grid-list';
import { MatIcon, MatIconModule } from '@angular/material/icon';
import { MatInputModule } from '@angular/material/input';
import { MatListModule } from '@angular/material/list';
import { MatMenuModule } from '@angular/material/menu';
import { MatPaginatorModule, MatPaginatorIntl } from '@angular/material/paginator';
import { MatProgressBarModule } from '@angular/material/progress-bar';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { MatRadioModule } from '@angular/material/radio';
import { MatSelectModule } from '@angular/material/select';
import { MatSidenavModule } from '@angular/material/sidenav';
import { MatSlideToggleModule } from '@angular/material/slide-toggle';
import { MatSliderModule } from '@angular/material/slider';
import { MatSnackBar, MatSnackBarModule } from '@angular/material/snack-bar';
import { MatSortModule } from '@angular/material/sort';
import { MatStepperModule } from '@angular/material/stepper';
import { MatTableModule } from '@angular/material/table';
import { MatTabsModule } from '@angular/material/tabs';
import { MatToolbarModule } from '@angular/material/toolbar';
import { MatTooltipModule } from '@angular/material/tooltip';
import {MatRippleModule} from '@angular/material/core';

import {MatTreeModule} from '@angular/material/tree';
import {getFrenchPaginatorIntl, getTranslatedPaginator} from './french-paginator-intl';

@NgModule({
    imports: [
        MatCheckboxModule,
        MatSelectModule,
        MatSlideToggleModule,
        MatInputModule,
        MatTooltipModule,
        MatTabsModule,
        MatSidenavModule,
        MatButtonModule,
        MatCardModule,
        MatButtonToggleModule,
        MatProgressSpinnerModule,
        MatProgressBarModule,
        MatToolbarModule,
        MatMenuModule,
        MatGridListModule,
        MatTableModule,
        MatPaginatorModule,
        MatSortModule,
        MatDatepickerModule,
        MatNativeDateModule,
        MatExpansionModule,
        MatAutocompleteModule,
        MatSnackBarModule,
        MatIconModule,
        MatDialogModule,
        MatListModule,
        MatChipsModule,
        MatStepperModule,
        MatRadioModule,
        MatSliderModule,
        MatBadgeModule,
        MatBottomSheetModule,
        MatTreeModule,
        MatRippleModule,
        DndModule.forRoot()
    ],
    exports: [
        MatCheckboxModule,
        MatSelectModule,
        MatSlideToggleModule,
        MatInputModule,
        MatTooltipModule,
        MatTabsModule,
        MatSidenavModule,
        MatButtonModule,
        MatCardModule,
        MatButtonToggleModule,
        MatProgressSpinnerModule,
        MatProgressBarModule,
        MatToolbarModule,
        MatMenuModule,
        MatGridListModule,
        MatTableModule,
        MatPaginatorModule,
        MatSortModule,
        MatDatepickerModule,
        MatNativeDateModule,
        MatExpansionModule,
        MatAutocompleteModule,
        MatSnackBarModule,
        MatIconModule,
        MatDialogModule,
        MatListModule,
        MatChipsModule,
        MatStepperModule,
        MatRadioModule,
        MatSliderModule,
        MatBadgeModule,
        MatBottomSheetModule,
        MatRippleModule,
        MatTreeModule,
        DndModule
    ],
    providers: [
        { provide: MatPaginatorIntl, useValue: getTranslatedPaginator('FR-fr') },
        { provide: MAT_DATE_LOCALE, useValue: 'FR-fr' },
        { provide: DateAdapter, useClass: AppDateAdapter },
        { provide: MAT_DATE_FORMATS, useValue: APP_DATE_FORMATS },
    ]
})
export class AppMaterialModule { }
