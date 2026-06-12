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
{extends file='page.tpl'}

{block name='page_content'}
    <section id="content" class="page-content card card-block">

        {if $success}
            <div class="alert alert-success" role="alert">
                <h3>{l s='Cancellation successfully submitted' d='Modules.Orderrevocator.Shop'}</h3>
                <p>{l s='Your cancellation has been received. We have sent a confirmation with all details and the exact time of receipt to the email address stored in our system.' d='Modules.Orderrevocator.Shop'}</p>
            </div>
        {else}
            <h2>{l s='Revoke order' d='Modules.Orderrevocator.Shop'}</h2>
            <p>{l s='You can use this form to quickly and easily revoke your order. The email address from your order will be used automatically.' d='Modules.Orderrevocator.Shop'}</p>
            <form action="{$action_url}" method="post">
                <div style="display:none !important;">
                    <label for="website_hp" class="col-md-3 form-control-label required">Website HP</label>
                    <input type="text" name="website_hp" id="website_hp" value="" autocomplete="off"/>
                </div>

                <div class="form-group row">
                    <label for="order_reference" class="col-md-3 form-control-label required">{l s='Order reference' d='Modules.Orderrevocator.Shop'}</label>
                    <div class="col-md-6">
                        <input class="form-control" name="order_reference" id="order_reference" type="text" required
                               value="{if isset($smarty.post.order_reference)}{$smarty.post.order_reference|escape:'html':'UTF-8'}{/if}">
                    </div>
                </div>

                <div class="form-group row">
                    <label for="message" class="col-md-3 form-control-label">{l s='Additional details / Reason (Optional)' d='Modules.Orderrevocator.Shop'}</label>
                    <div class="col-md-6">
                    <textarea class="form-control" name="message" id="message"
                              rows="4">{if isset($smarty.post.message)}{$smarty.post.message|escape:'html':'UTF-8'}{/if}</textarea>
                    </div>
                </div>

                <footer class="form-footer text-sm-center">
                    <button class="btn btn-primary" name="submit_revocation" type="submit">
                        {l s='Revoke now' d='Modules.Orderrevocator.Shop'}
                    </button>
                </footer>
            </form>
        {/if}
    </section>
{/block}