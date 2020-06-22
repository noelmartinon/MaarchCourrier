import { NgModule } from '@angular/core';

import { TimeAgoPipe } from '../plugins/timeAgo.pipe';
import { TimeLimitPipe } from '../plugins/timeLimit.pipe';
import { FilterListPipe } from '../plugins/filterList.pipe';
import { FullDatePipe } from '../plugins/fullDate.pipe';
import { SafeHtmlPipe } from '../plugins/safeHtml.pipe';
import { SecureUrlPipe } from '../plugins/secureUrl.pipe';
import { NgStringPipesModule } from 'ngx-pipes';
import { LatinisePipe } from 'ngx-pipes';
import { CookieService } from 'ngx-cookie-service';
import { SortPipe } from '../plugins/sorting.pipe';

@NgModule({
    imports: [
        NgStringPipesModule
    ],
    declarations: [
        TimeAgoPipe,
        TimeLimitPipe,
        FilterListPipe,
        FullDatePipe,
        SafeHtmlPipe,
        SecureUrlPipe,
        SortPipe
    ],
    exports: [
        NgStringPipesModule,
        TimeAgoPipe,
        TimeLimitPipe,
        FilterListPipe,
        FullDatePipe,
        SafeHtmlPipe,
        SecureUrlPipe,
        SortPipe
    ],
    entryComponents: [
    ],
    providers: [
        LatinisePipe,
        CookieService,
    ],
})
export class AppServiceModule { }
