{*
 * Copyright 2026 Stefan Schulz
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @author    Stefan Schulz <schulz@the-loom.de>
 * @copyright 2026 Stefan Schulz
 * @license   http://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 *}
<div class="col-md-2 links wrapper">
    <p class="h3 hidden-sm-down">{l s='Legal' d='Modules.Orderrevocator.Shop'}</p>

    <div class="title clearfix hidden-md-up" data-target="#footer_revocation_collapse" data-toggle="collapse">
        <span>{l s='Legal' d='Modules.Orderrevocator.Shop'}</span>
        <span class="float-xs-right">
            <span class="navbar-toggler-bar add"></span>
            <span class="navbar-toggler-bar remove"></span>
        </span>
    </div>

    <ul id="footer_revocator_collapse" class="collapse item-list">
        <li  style="list-style: none; margin-top: 10px;">
            <a class="btn btn-primary btn-orderrevocator" href="{$revocation_url|escape:'html':'UTF-8'}" title="{l s='Revoke order' d='Modules.Orderrevocator.Shop'}" rel="nofollow">
                <i class="material-icons font-size-16" style="vertical-align: middle; margin-right: 5px;">history</i>
                {l s='Revoke order' d='Modules.Orderrevocator.Shop'}
            </a>
        </li>
    </ul>
</div>