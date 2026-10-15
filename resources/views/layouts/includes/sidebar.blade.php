<!--begin::Page-->
<div class="d-flex flex-row flex-column-fluid page">
    <!--begin::Aside-->
    <div class="aside aside-left aside-fixed d-flex flex-column flex-row-auto lft-sidebar" id="kt_aside" aria-label="التنقل الرئيسي">
        <!--begin::Brand-->
        <div class="brand flex-column-auto" id="kt_brand">
            <a href="{{ route('main') }}" class="brand-logo lft-sidebar-brand">
                <img src="{{ asset('assets/media/logo.png') }}" alt="Leader for Trans" class="lft-sidebar-logo" />
                <span class="lft-brand-titles">
                    <span class="lft-brand-title">Leader for Trans</span>
                    <span class="lft-brand-badge">إدارة</span>
                </span>
            </a>
            <button class="brand-toggle lft-sidebar-collapse" id="kt_aside_toggle" type="button" aria-label="طي قائمة التنقل" title="طي القائمة">
                <i class="la la-angle-double-right" aria-hidden="true"></i>
            </button>
        </div>
        <!--end::Brand-->

        <div class="lft-sidebar-search" role="search">
            <i class="la la-search" aria-hidden="true"></i>
            <input type="search" id="lftSidebarSearch" placeholder="ابحث عن صفحة..." aria-label="ابحث عن صفحة" autocomplete="off">
        </div>

        <!--begin::Aside Menu-->
        <div class="aside-menu-wrapper flex-column-fluid" id="kt_aside_menu_wrapper">
            <!--begin::Menu Container-->
            <div id="kt_aside_menu" class="aside-menu" data-menu-vertical="1" data-menu-scroll="0"
                data-menu-dropdown-timeout="500">
                <!--begin::Menu Nav-->
                <ul class="menu-nav pt-0">
                    <li class="menu-item menu-item-submenu" aria-haspopup="true" data-menu-toggle="hover">
                        <a href="{{ route('main') }}" class="menu-link">
                            <span class="svg-icon menu-icon">
                                <i class="la la-tachometer-alt"></i>
                            </span>
                            <span class="menu-text">{{ __('main.home') }}</span>
                        </a>
                    </li>


                    @if (auth()->user()->hasPermissionTo('containers.index'))
                        <li class="menu-item menu-item-submenu" aria-haspopup="true" data-menu-toggle="hover">
                            <a href="javascript:;" class="menu-link menu-toggle">
                                <span class="svg-icon menu-icon">
                                    <i class="la la-box"></i>
                                </span>
                                <span class="menu-text">{{ __('main.containers') }}</span>
                                <i class="menu-arrow"></i>
                            </a>
                            <div class="menu-submenu">
                                <i class="menu-arrow"></i>
                                <ul class="menu-subnav">
                                    <li class="menu-item menu-item-parent" aria-haspopup="true">
                                        <span class="menu-link">
                                            <span class="menu-text">{{ __('main.containers') }}</span>
                                        </span>
                                    </li>
                                    @if (auth()->user()->hasPermissionTo('containers.index'))
                                        <li class="menu-item menu-item-submenu" aria-haspopup="true"
                                            data-menu-toggle="hover">
                                            <a href="{{ route('containers.index') }}" class="menu-link">
                                                <span class="menu-text">{{ __('main.containers') }}</span>
                                            </a>
                                        </li>
                                    @endif
                                </ul>
                            </div>
                        </li>
                    @endif

                    @if (auth()->user()->hasPermissionTo('serviceCategories.index') ||
                            auth()->user()->hasPermissionTo('services.index') ||
                            auth()->user()->hasPermissionTo('financial_custody_agents.index'))
                        <li class="menu-item menu-item-submenu" aria-haspopup="true" data-menu-toggle="hover">
                            <a href="javascript:;" class="menu-link menu-toggle">
                                <span class="svg-icon menu-icon">
                                    <i class="la la-layer-group"></i>
                                </span>
                                <span class="menu-text">{{ __('main.services') }}</span>
                                <i class="menu-arrow"></i>
                            </a>
                            <div class="menu-submenu">
                                <i class="menu-arrow"></i>
                                <ul class="menu-subnav">
                                    @if (auth()->user()->hasPermissionTo('serviceCategories.index'))
                                        <li class="menu-item menu-item-submenu" aria-haspopup="true"
                                            data-menu-toggle="hover">
                                            <a href="{{ route('serviceCategories.index') }}" id="serviceCategories-link"
                                                class="menu-link">
                                                <span class="menu-text">{{ __('main.serviceCategories') }}</span>
                                            </a>
                                        </li>
                                    @endif

                                    @if (auth()->user()->hasPermissionTo('services.index'))
                                        <li class="menu-item menu-item-submenu" aria-haspopup="true"
                                            data-menu-toggle="hover">
                                            <a href="{{ route('services.index') }}" id="services-link"
                                                class="menu-link">
                                                <span class="menu-text">{{ __('main.services') }}</span>
                                            </a>
                                        </li>
                                    @endif
                                </ul>
                            </div>
                        </li>
                    @endif



                    @if (auth()->user()->hasPermissionTo('yards.index'))
                        <li class="menu-item menu-item-submenu" aria-haspopup="true" data-menu-toggle="hover">

                            <a href="{{ route('yards.index') }}" id="yards-link" class="menu-link">
                                <span class="svg-icon menu-icon">
                                    <i class="la la-warehouse"></i>
                                </span>
                                <span class="menu-text">{{ __('main.yards') }}</span>
                            </a>
                        </li>
                    @endif

                    @if (auth()->user()->hasPermissionTo('shippingAgents.index'))
                        <li class="menu-item menu-item-submenu" aria-haspopup="true" data-menu-toggle="hover">

                            <a href="{{ route('shippingAgents.index') }}" id="shippingAgents-link"
                                class="menu-link">
                                <span class="svg-icon menu-icon">
                                    <i class="la la-ship"></i>
                                </span>
                                <span class="menu-text">{{ __('main.shippingAgents') }}</span>
                            </a>
                        </li>
                    @endif


                    @if (auth()->user()->hasPermissionTo('cities.index'))
                        <li class="menu-item menu-item-submenu" aria-haspopup="true" data-menu-toggle="hover">
                            <a href="javascript:;" class="menu-link menu-toggle">
                                <span class="svg-icon menu-icon">
                                    <i class="la la-map-marked-alt"></i>
                                </span>
                                <span class="menu-text">{{ __('main.cities_and_regions') }}</span>
                                <i class="menu-arrow"></i>
                            </a>
                            <div class="menu-submenu">
                                <i class="menu-arrow"></i>
                                <ul class="menu-subnav">
                                    <li class="menu-item menu-item-parent" aria-haspopup="true">
                                        <span class="menu-link">
                                            <span class="menu-text">{{ __('main.cities_and_regions') }}</span>
                                        </span>
                                    </li>
                                    @if (auth()->user()->hasPermissionTo('cities.index'))
                                        <li class="menu-item menu-item-submenu" aria-haspopup="true"
                                            data-menu-toggle="hover">
                                            <a href="{{ route('citiesAndRegions.index') }}" class="menu-link">
                                                <span class="menu-text">{{ __('main.cities_and_regions') }}</span>
                                            </a>
                                        </li>
                                    @endif
                                </ul>
                            </div>
                        </li>
                    @endif



                    @if (auth()->user()->hasPermissionTo('companies.index') ||
                            auth()->user()->hasPermissionTo('superagents.index') ||
                            auth()->user()->hasPermissionTo('agents.index') ||
                            auth()->user()->hasPermissionTo('agents.index') ||
                            auth()->user()->hasPermissionTo('drivers.index') ||
                            auth()->user()->hasPermissionTo('cars.index') ||
                            auth()->user()->hasPermissionTo('employees.index') ||
                            auth()->user()->hasPermissionTo('employees.index'))
                        <li class="menu-item menu-item-submenu" aria-haspopup="true" data-menu-toggle="hover">
                            <a href="javascript:;" class="menu-link menu-toggle">
                                <span class="svg-icon menu-icon">
                                    <i class="la la-users"></i>
                                </span>
                                <span class="menu-text">{{ __('main.clients') }}</span>
                                <i class="menu-arrow"></i>
                            </a>
                            <div class="menu-submenu">
                                <i class="menu-arrow"></i>
                                <ul class="menu-subnav">
                                    <li class="menu-item menu-item-parent" aria-haspopup="true">
                                        <span class="menu-link">
                                            <span class="menu-text">{{ __('main.companies') }}</span>
                                        </span>
                                    </li>
                                    @if (auth()->user()->hasPermissionTo('companies.index'))
                                        <li class="menu-item menu-item-submenu" aria-haspopup="true"
                                            data-menu-toggle="hover">
                                            <a href="{{ route('companies.index') }}" class="menu-link">
                                                <span class="menu-text">{{ __('main.companies') }}</span>
                                            </a>
                                        </li>
                                    @endif

                                    <li class="menu-item menu-item-submenu" aria-haspopup="true"
                                        data-menu-toggle="hover">
                                        <a href="{{ route('private-companies.index') }}" class="menu-link">
                                            <span class="menu-text">الشركات الخاصة</span>
                                        </a>
                                    </li>


                                    @if (auth()->user()->hasPermissionTo('employees.index'))
                                        <li class="menu-item menu-item-submenu" aria-haspopup="true"
                                            data-menu-toggle="hover">
                                            <a href="{{ route('employees.index') }}" class="menu-link">
                                                <span class="menu-text">{{ __('main.employees') }}</span>
                                            </a>
                                        </li>
                                    @endif

                                    @if (auth()->user()->hasPermissionTo('factories.index'))
                                        <li class="menu-item menu-item-submenu" aria-haspopup="true"
                                            data-menu-toggle="hover">
                                            <a href="{{ route('factories.index') }}" class="menu-link">
                                                <span class="menu-text">{{ __('main.factories') }}</span>
                                            </a>
                                        </li>
                                    @endif

                                    @if (auth()->user()->hasPermissionTo('branches.index'))
                                        <li class="menu-item menu-item-submenu" aria-haspopup="true"
                                            data-menu-toggle="hover">
                                            <a href="{{ route('branches.index') }}" class="menu-link">
                                                <span class="menu-text">{{ __('main.branches') }}</span>
                                            </a>
                                        </li>
                                    @endif
                                </ul>
                            </div>
                        </li>
                    @endif


                    @if (auth()->user()->hasPermissionTo('companies.index') ||
                            auth()->user()->hasPermissionTo('superagents.index') ||
                            auth()->user()->hasPermissionTo('agents.index') ||
                            auth()->user()->hasPermissionTo('agents.index') ||
                            auth()->user()->hasPermissionTo('drivers.index') ||
                            auth()->user()->hasPermissionTo('cars.index') ||
                            auth()->user()->hasPermissionTo('employees.index') ||
                            auth()->user()->hasPermissionTo('employees.index'))
                        <li class="menu-item menu-item-submenu" aria-haspopup="true" data-menu-toggle="hover">
                            <a href="javascript:;" class="menu-link menu-toggle">
                                <span class="svg-icon menu-icon">
                                    <i class="la la-truck"></i>
                                </span>
                                <span class="menu-text">{{ __('main.transportations') }}</span>
                                <i class="menu-arrow"></i>
                            </a>
                            <div class="menu-submenu">
                                <i class="menu-arrow"></i>
                                <ul class="menu-subnav">
                                    <li class="menu-item menu-item-parent" aria-haspopup="true">
                                        <span class="menu-link">
                                            <span class="menu-text">{{ __('main.transportations') }}</span>
                                        </span>
                                    </li>

                                    @if (auth()->user()->hasPermissionTo('drivers.index'))
                                        <li class="menu-item menu-item-submenu" aria-haspopup="true"
                                            data-menu-toggle="hover">
                                            <a href="{{ route('drivers.index') }}" class="menu-link">
                                                <span class="menu-text">{{ __('main.drivers') }}</span>
                                            </a>
                                        </li>
                                    @endif

                                    @if (auth()->user()->hasPermissionTo('cars.index'))
                                        <li class="menu-item menu-item-submenu" aria-haspopup="true"
                                            data-menu-toggle="hover">
                                            <a href="{{ route('cars.index') }}" class="menu-link">
                                                <span class="menu-text">{{ __('main.cars') }}</span>
                                            </a>
                                        </li>
                                    @endif


                                </ul>
                            </div>
                        </li>
                    @endif

                    @if (auth()->user()->hasPermissionTo('companies.index') ||
                            auth()->user()->hasPermissionTo('superagents.index') ||
                            auth()->user()->hasPermissionTo('agents.index') ||
                            auth()->user()->hasPermissionTo('agents.index') ||
                            auth()->user()->hasPermissionTo('drivers.index') ||
                            auth()->user()->hasPermissionTo('cars.index') ||
                            auth()->user()->hasPermissionTo('employees.index') ||
                            auth()->user()->hasPermissionTo('employees.index'))
                        <li class="menu-item menu-item-submenu" aria-haspopup="true" data-menu-toggle="hover">
                            <a href="javascript:;" class="menu-link menu-toggle">
                                <span class="svg-icon menu-icon">
                                    <i class="la la-id-badge"></i>
                                </span>
                                <span class="menu-text">{{ __('main.agents') }}</span>
                                <i class="menu-arrow"></i>
                            </a>
                            <div class="menu-submenu">
                                <i class="menu-arrow"></i>
                                <ul class="menu-subnav">
                                    <li class="menu-item menu-item-parent" aria-haspopup="true">
                                        <span class="menu-link">
                                            <span class="menu-text">{{ __('main.agents') }}</span>
                                        </span>
                                    </li>

                                    @if (auth()->user()->hasPermissionTo('superagents.index'))
                                        <li class="menu-item menu-item-submenu" aria-haspopup="true"
                                            data-menu-toggle="hover">
                                            <a href="{{ route('superagents.index') }}" class="menu-link">
                                                <span class="menu-text">{{ __('main.superagents') }}</span>
                                            </a>
                                        </li>
                                    @endif

                                    @if (auth()->user()->hasPermissionTo('agents.index'))
                                        <li class="menu-item menu-item-submenu" aria-haspopup="true"
                                            data-menu-toggle="hover">
                                            <a href="{{ route('agents.index') }}" class="menu-link">
                                                <span class="menu-text">{{ __('main.agents') }}</span>
                                            </a>
                                        </li>
                                        <li class="menu-item" aria-haspopup="true">
                                            <a href="{{ route('agent-photos.index') }}" class="menu-link">
                                                <span class="menu-text">صور المناديب</span>
                                            </a>
                                        </li>
                                    @endif
                                </ul>
                            </div>
                        </li>
                    @endif

                    {{-- الموردين: قائمة ظاهرة بجانب المندوبين/العملاء --}}
                    @if (in_array('Admin', auth()->user()->roles->pluck('name')->toArray()) || has_app_permission('suppliers.index'))
                        <li class="menu-item menu-item-submenu {{ request()->is('dashboard/suppliers*') || request()->is('dashboard/receipts*') ? 'menu-item-open menu-item-here' : '' }}"
                            aria-haspopup="true"
                            data-menu-toggle="hover">
                            <a href="javascript:;" class="menu-link menu-toggle">
                                <span class="svg-icon menu-icon">
                                    <i class="la la-handshake"></i>
                                </span>
                                <span class="menu-text">{{ __('main.suppliers') }}</span>
                                <i class="menu-arrow"></i>
                            </a>
                            <div class="menu-submenu">
                                <i class="menu-arrow"></i>
                                <ul class="menu-subnav">
                                    <li class="menu-item menu-item-parent" aria-haspopup="true">
                                        <span class="menu-link">
                                            <span class="menu-text">{{ __('main.suppliers') }}</span>
                                        </span>
                                    </li>
                                    <li class="menu-item" aria-haspopup="true">
                                        <a href="{{ route('suppliers.index') }}" class="menu-link">
                                            <i class="menu-bullet menu-bullet-dot"><span></span></i>
                                            <span class="menu-text">قائمة الموردين</span>
                                        </a>
                                    </li>
                                    <li class="menu-item" aria-haspopup="true">
                                        <a href="{{ route('suppliers.create') }}" class="menu-link">
                                            <i class="menu-bullet menu-bullet-dot"><span></span></i>
                                            <span class="menu-text">إضافة مورد</span>
                                        </a>
                                    </li>
                                    <li class="menu-item" aria-haspopup="true">
                                        <a href="{{ route('receipts.index') }}" class="menu-link">
                                            <i class="menu-bullet menu-bullet-dot"><span></span></i>
                                            <span class="menu-text">الإيصالات</span>
                                        </a>
                                    </li>
                                    <li class="menu-item" aria-haspopup="true">
                                        <a href="{{ route('receipts.create') }}" class="menu-link">
                                            <i class="menu-bullet menu-bullet-dot"><span></span></i>
                                            <span class="menu-text">إضافة إيصال</span>
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </li>
                    @endif

                    @if (auth()->user()->hasPermissionTo('permissions.index'))
                        <li class="menu-item menu-item-submenu" aria-haspopup="true" data-menu-toggle="hover">

                            <a href="{{ route('permissions.index') }}" id="permissions-link" class="menu-link">
                                <span class="svg-icon menu-icon">
                                    <i class="la la-shield-alt"></i>
                                </span>
                                <span class="menu-text">{{ __('main.permissions') }}</span>
                            </a>
                        </li>
                    @endif


                    @if (in_array('Admin', auth()->user()->roles->pluck('name')->toArray()))
                        <li class="menu-item menu-item-submenu" aria-haspopup="true" data-menu-toggle="hover">
                            <a href="javascript:;" class="menu-link menu-toggle">
                                <span class="svg-icon menu-icon">
                                    <i class="la la-user-cog"></i>
                                </span>
                                <span class="menu-text">{{ __('main.manage_users') }}</span>
                                <i class="menu-arrow"></i>
                            </a>
                            <div class="menu-submenu">
                                <i class="menu-arrow"></i>
                                <ul class="menu-subnav">
                                    <li class="menu-item menu-item-parent" aria-haspopup="true">
                                        <span class="menu-link">
                                            <span class="menu-text">{{ __('main.manage_users') }}</span>
                                        </span>
                                    </li>



                                    @foreach (\Spatie\Permission\Models\Role::all() as $role)
                                        <li class="menu-item menu-item-submenu" aria-haspopup="true"
                                            data-menu-toggle="hover">
                                            <a href="{{ route('users.index', ['role' => $role->name]) }}"
                                                id="static-pages-link" class="menu-link">
                                                <span class="menu-text">{{ $role->name }}</span>
                                            </a>
                                        </li>
                                    @endforeach
                                    <li class="menu-item menu-item-submenu" aria-haspopup="true"
                                        data-menu-toggle="hover">
                                        <a href="{{ route('users.index') }}" id="static-pages-link"
                                            class="menu-link">
                                            <span class="menu-text">{{ __("main.They_don't_have_roles") }}</span>
                                        </a>
                                    </li>


                                </ul>
                            </div>
                        </li>
                    @endif

                    @if (auth()->user()->hasPermissionTo('staticPages.index') ||
                            auth()->user()->hasPermissionTo('ourServices.index') ||
                            auth()->user()->hasPermissionTo('chooseUs.index') ||
                            auth()->user()->hasPermissionTo('sponsers.index') ||
                            auth()->user()->hasPermissionTo('reviews.index') ||
                            auth()->user()->hasPermissionTo('settings.index') ||
                            auth()->user()->hasPermissionTo('contactUs.index'))
                        <li class="menu-item menu-item-submenu" aria-haspopup="true" data-menu-toggle="hover">
                            <a href="javascript:;" class="menu-link menu-toggle">
                                <span class="svg-icon menu-icon">
                                    <i class="la la-cog"></i>
                                </span>
                                <span class="menu-text">{{ __('main.website_setting') }}</span>
                                <i class="menu-arrow"></i>
                            </a>
                            <div class="menu-submenu">
                                <i class="menu-arrow"></i>
                                <ul class="menu-subnav">
                                    <li class="menu-item menu-item-parent" aria-haspopup="true">
                                        <span class="menu-link">
                                            <span class="menu-text">{{ __('main.website_setting') }}</span>
                                        </span>
                                    </li>




                                    @if (auth()->user()->hasPermissionTo('staticPages.index'))
                                        <li class="menu-item menu-item-submenu" aria-haspopup="true"
                                            data-menu-toggle="hover">
                                            <a href="{{ route('staticPages.index') }}" id="static-pages-link"
                                                class="menu-link">
                                                <span class="menu-text">{{ __('main.staticPages') }}</span>
                                            </a>
                                        </li>
                                    @endif
                                    @if (auth()->user()->hasPermissionTo('ourServices.index'))
                                        <li class="menu-item menu-item-submenu" aria-haspopup="true"
                                            data-menu-toggle="hover">
                                            <a href="{{ route('ourServices.index') }}" id="our-services-link"
                                                class="menu-link">
                                                <span class="menu-text">{{ __('main.ourServices') }}</span>
                                            </a>
                                        </li>
                                    @endif

                                    @if (auth()->user()->hasPermissionTo('chooseUs.index'))
                                        <li class="menu-item menu-item-submenu" aria-haspopup="true"
                                            data-menu-toggle="hover">
                                            <a href="{{ route('chooseUs.index') }}" id="choose-us-link"
                                                class="menu-link">
                                                <span class="menu-text">{{ __('main.chooseUs') }}</span>
                                            </a>
                                        </li>
                                    @endif

                                    @if (auth()->user()->hasPermissionTo('sponsers.index'))
                                        <li class="menu-item menu-item-submenu" aria-haspopup="true"
                                            data-menu-toggle="hover">
                                            <a href="{{ route('sponsers.index') }}" id="sponsers-link"
                                                class="menu-link">
                                                <span class="menu-text">{{ __('main.sponsers') }}</span>
                                            </a>
                                        </li>
                                    @endif

                                    @if (auth()->user()->hasPermissionTo('reviews.index'))
                                        <li class="menu-item menu-item-submenu" aria-haspopup="true"
                                            data-menu-toggle="hover">
                                            <a href="{{ route('reviews.index') }}" id="reviews-link"
                                                class="menu-link">
                                                <span class="menu-text">{{ __('main.reviews') }}</span>
                                            </a>
                                        </li>
                                    @endif

                                    @if (auth()->user()->hasPermissionTo('settings.index'))
                                        <li class="menu-item menu-item-submenu" aria-haspopup="true"
                                            data-menu-toggle="hover">
                                            <a href="{{ route('settings.edit', 1) }}" id="settings-link"
                                                class="menu-link">
                                                <span class="menu-text">{{ __('main.settings') }}</span>
                                            </a>
                                        </li>
                                    @endif

                                    @if (auth()->user()->hasPermissionTo('contactUs.index'))
                                        <li class="menu-item menu-item-submenu" aria-haspopup="true"
                                            data-menu-toggle="hover">
                                            <a href="{{ route('contactUs.index') }}" id="Contact-us-link"
                                                class="menu-link">
                                                <span class="menu-text">{{ __('main.contactUs') }}</span>
                                            </a>
                                        </li>
                                    @endif
                                </ul>
                            </div>
                        </li>
                    @endif


                    @if (auth()->user()->hasPermissionTo('bookings.index'))
                        <li class="menu-item menu-item-submenu" aria-haspopup="true" data-menu-toggle="hover">
                            <a href="javascript:;" class="menu-link menu-toggle">
                                <span class="svg-icon menu-icon">
                                    <i class="la la-clipboard-list"></i>
                                </span>
                                <span class="menu-text">{{ __('main.bookings') }}</span>
                                <i class="menu-arrow"></i>
                            </a>
                            <div class="menu-submenu">
                                <i class="menu-arrow"></i>
                                <ul class="menu-subnav">
                                    <li class="menu-item menu-item-parent" aria-haspopup="true">
                                        <span class="menu-link">
                                            <span class="menu-text">{{ __('main.bookings') }}</span>
                                        </span>
                                    </li>

                                    @if (auth()->user()->hasPermissionTo('bookings.index'))
                                        <li class="menu-item menu-item-submenu" aria-haspopup="true"
                                            data-menu-toggle="hover">
                                            <a href="{{ route('bookings.index') }}" class="menu-link">
                                                <span class="menu-text">{{ __('main.bookings') }}</span>
                                            </a>
                                        </li>
                                    @endif

                                </ul>
                            </div>
                        </li>
                    @endif



                    @if (has_app_permission('accounts.index') || has_app_permission('daily_reports.index') || has_app_permission('suppliers.index'))
                        <li class="menu-item menu-item-submenu" aria-haspopup="true" data-menu-toggle="hover">
                            <a href="javascript:;" class="menu-link menu-toggle">
                                <span class="svg-icon menu-icon">
                                    <i class="la la-chart-bar"></i>
                                </span>
                                <span class="menu-text">الحسابات والتقارير</span>
                                <i class="menu-arrow"></i>
                            </a>
                            <div class="menu-submenu">
                                <i class="menu-arrow"></i>
                                <ul class="menu-subnav">
                                    <li class="menu-item menu-item-parent" aria-haspopup="true">
                                        <span class="menu-link">
                                            <span class="menu-text">الحسابات والتقارير</span>
                                        </span>
                                    </li>
                                    @if (has_app_permission('accounts.index'))
                                        <li class="menu-item menu-item-submenu" aria-haspopup="true"
                                            data-menu-toggle="hover">
                                            <a href="{{ route('accounts.index') }}" class="menu-link">
                                                <span class="menu-text">حسابات الشركات</span>
                                            </a>
                                        </li>
                                        <li class="menu-item menu-item-submenu" aria-haspopup="true"
                                            data-menu-toggle="hover">
                                            <a href="{{ route('accounts.checks.index') }}" class="menu-link">
                                                <i class="menu-bullet menu-bullet-dot"><span></span></i>
                                                <span class="menu-text">الشيكات</span>
                                            </a>
                                        </li>
                                    @endif
                                    @if (has_app_permission('suppliers.index'))
                                        <li class="menu-item menu-item-submenu" aria-haspopup="true"
                                            data-menu-toggle="hover">
                                            <a href="{{ route('suppliers.index') }}" class="menu-link">
                                                <i class="menu-bullet menu-bullet-dot"><span></span></i>
                                                <span class="menu-text">{{ __('main.suppliers') }}</span>
                                            </a>
                                        </li>
                                        <li class="menu-item menu-item-submenu" aria-haspopup="true"
                                            data-menu-toggle="hover">
                                            <a href="{{ route('receipts.index') }}" class="menu-link">
                                                <i class="menu-bullet menu-bullet-dot"><span></span></i>
                                                <span class="menu-text">إيصالات الموردين</span>
                                            </a>
                                        </li>
                                    @endif
                                    @if (has_app_permission('cars.index'))
                                        <li class="menu-item menu-item-submenu" aria-haspopup="true"
                                            data-menu-toggle="hover">
                                            <a href="{{ route('cars.index') }}" class="menu-link">
                                                <i class="menu-bullet menu-bullet-dot"><span></span></i>
                                                <span class="menu-text">حسابات السيارات</span>
                                            </a>
                                        </li>
                                    @endif
                                    @if (has_app_permission('daily_reports.index'))
                                        <li class="menu-item menu-item-submenu" aria-haspopup="true"
                                            data-menu-toggle="hover">
                                            <a href="{{ route('reports.general_expenses') }}" class="menu-link">
                                                <i class="menu-bullet menu-bullet-dot"><span></span></i>
                                                <span class="menu-text">المصروفات العامة</span>
                                            </a>
                                        </li>
                                    @endif
                                    @if (has_app_permission('accounts.index'))
                                        <li class="menu-item menu-item-submenu" aria-haspopup="true"
                                            data-menu-toggle="hover">
                                            <a href="{{ route('accounts.profit-loss') }}" class="menu-link">
                                                <i class="menu-bullet menu-bullet-dot"><span></span></i>
                                                <span class="menu-text">تقرير الأرباح والخسائر</span>
                                            </a>
                                        </li>
                                    @endif
                                </ul>
                            </div>
                        </li>
                    @endif


                    @if (auth()->user()->hasPermissionTo('serviceCategories.index') ||
                            auth()->user()->hasPermissionTo('services.index') ||
                            auth()->user()->hasPermissionTo('financial_custody_agents.index'))
                        <li class="menu-item menu-item-submenu" aria-haspopup="true" data-menu-toggle="hover">
                            <a href="javascript:;" class="menu-link menu-toggle">
                                <span class="svg-icon menu-icon">
                                    <i class="la la-wallet"></i>
                                </span>
                                <span class="menu-text">{{ __('main.finance') }}</span>
                                <i class="menu-arrow"></i>
                            </a>
                            <div class="menu-submenu">
                                <i class="menu-arrow"></i>
                                <ul class="menu-subnav">
                                    @if (auth()->user()->hasPermissionTo('banks.index'))
                                        <li class="menu-item menu-item-submenu" aria-haspopup="true"
                                            data-menu-toggle="hover">
                                            <a href="{{ route('banks.index') }}" id="banks-link" class="menu-link">
                                                <span class="menu-text">{{ __('main.banks') }}</span>
                                            </a>
                                        </li>
                                    @endif

                                    @if (auth()->user()->hasPermissionTo('vaults.index'))
                                        <li class="menu-item menu-item-submenu" aria-haspopup="true"
                                            data-menu-toggle="hover">
                                            <a href="{{ route('vaults.index') }}" id="vaults-link"
                                                class="menu-link">
                                                <span class="menu-text">{{ __('main.vaults') }}</span>
                                            </a>
                                        </li>
                                    @endif

                                    @if (auth()->user()->hasPermissionTo('financial_custody_agents.index'))
                                        <li class="menu-item menu-item-submenu" aria-haspopup="true"
                                            data-menu-toggle="hover">
                                            <a href="{{ route('financial_custody_agents.index') }}"
                                                id="services-link" class="menu-link">
                                                <span
                                                    class="menu-text">{{ __('main.financial_custody_agents') }}</span>
                                            </a>
                                        </li>
                                    @endif

                                    @if (auth()->user()->hasPermissionTo('financial_custody_agents.index'))
                                        <li class="menu-item menu-item-submenu" aria-haspopup="true"
                                            data-menu-toggle="hover">
                                            <a href="{{ route('financial_custody_superagents.index') }}"
                                                id="services-link" class="menu-link">
                                                <span
                                                    class="menu-text">{{ __('main.financial_custody_superagents') }}</span>
                                            </a>
                                        </li>
                                    @endif
                                </ul>
                            </div>
                        </li>
                    @endif










                </ul>
                <!--end::Menu Nav-->
                <p class="lft-sidebar-empty" id="lftSidebarEmpty" hidden>لا توجد صفحة بهذا الاسم</p>
            </div>
            <!--end::Menu Container-->
        </div>
        <!--end::Aside Menu-->

        <div class="lft-sidebar-footer">
            <button type="button" class="lft-sidebar-theme" id="lftThemeSimpleToggle" aria-label="تبديل المظهر" title="تبديل المظهر">
                <i class="la la-sun lft-sidebar-theme-sun" aria-hidden="true"></i>
                <i class="la la-moon lft-sidebar-theme-moon" aria-hidden="true"></i>
            </button>
            <div class="lft-sidebar-user">
                <span class="lft-sidebar-user-avatar" aria-hidden="true">{{ mb_substr(auth()->user()->name ?? 'م', 0, 1) }}</span>
                <span class="lft-sidebar-user-info">
                    <span class="lft-sidebar-user-name">{{ auth()->user()->name }}</span>
                    <span class="lft-sidebar-user-email">{{ auth()->user()->email }}</span>
                </span>
                <a href="{{ route('logout') }}" class="lft-sidebar-logout" aria-label="تسجيل الخروج" title="تسجيل الخروج"
                    onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                    <i class="la la-sign-out-alt" aria-hidden="true"></i>
                </a>
            </div>
        </div>
    </div>
    <!--end::Aside-->
    <!--begin::Wrapper-->
    <div class="d-flex flex-column flex-row-fluid wrapper" id="kt_wrapper">

        <!--begin::Header-->
        <div id="kt_header" class="header header-fixed">
            <!--begin::Container-->
            <div class="container-fluid d-flex align-items-stretch justify-content-between px-6">
                <!--begin::Page Title & Breadcrumb-->
                <div class="d-flex align-items-center flex-wrap mr-4 py-3">
                    <div class="d-flex flex-column">
                        <h4 class="text-dark font-weight-bolder my-1 mr-3 font-size-h4" style="color: var(--text-primary) !important;">
                            @yield('admin-page-title', 'لوحة التحكم')
                        </h4>
                        <ul class="breadcrumb breadcrumb-transparent breadcrumb-dot font-weight-bold p-0 my-1 font-size-sm">
                            <li class="breadcrumb-item text-muted">
                                <a href="{{ route('main') }}" class="text-muted text-hover-primary">الرئيسية</a>
                            </li>
                            <li class="breadcrumb-item text-muted">
                                <span>@yield('admin-page-title', 'لوحة التحكم')</span>
                            </li>
                        </ul>
                    </div>
                </div>
                <!--end::Page Title & Breadcrumb-->

                <!--begin::Topbar Actions-->
                <div class="d-flex align-items-center">
                    <!-- Global Search -->
                    <div class="lft-topbar-search d-none d-lg-block mr-4">
                        <i class="fas fa-search lft-topbar-search-icon"></i>
                        <input type="text" placeholder="البحث في النظام..." aria-label="البحث في النظام">
                    </div>

                    <div class="lft-topbar-actions">
                        <!-- Light / Dark Mode Toggle Pill -->
                        <div class="lft-theme-toggle mr-2" role="group" aria-label="تبديل المظهر">
                            <button type="button" class="lft-theme-toggle-btn active" data-theme-val="light" title="المظهر الفاتح">
                                <i class="fas fa-sun"></i>
                            </button>
                            <button type="button" class="lft-theme-toggle-btn" data-theme-val="dark" title="المظهر الداكن">
                                <i class="fas fa-moon"></i>
                            </button>
                        </div>

                        <!-- Notifications Button -->
                        <div class="dropdown mr-2">
                            <button type="button" class="lft-icon-btn" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="الإشعارات">
                                <i class="far fa-bell"></i>
                                <span class="lft-badge-counter">5</span>
                            </button>
                            <div class="dropdown-menu dropdown-menu-right p-0 m-0 dropdown-menu-md">
                                <div class="p-4 border-bottom d-flex justify-content-between align-items-center">
                                    <h6 class="font-weight-bolder mb-0">التنبيهات</h6>
                                    <span class="badge badge-light-primary">5 جديدة</span>
                                </div>
                                <div class="p-3 font-size-sm">
                                    <div class="d-flex align-items-center py-2 border-bottom">
                                        <div class="w-8px h-8px rounded-circle bg-success mr-2"></div>
                                        <span>تم إنشاء حجز جديد #509</span>
                                    </div>
                                    <div class="d-flex align-items-center py-2 border-bottom">
                                        <div class="w-8px h-8px rounded-circle bg-primary mr-2"></div>
                                        <span>تم اعتماد مرحلة تحميل لحاوية</span>
                                    </div>
                                    <div class="d-flex align-items-center py-2">
                                        <div class="w-8px h-8px rounded-circle bg-warning mr-2"></div>
                                        <span>شيكات مستحقة خلال 3 أيام</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Fullscreen Toggle Button -->
                        <button type="button" class="lft-icon-btn mr-3 d-none d-sm-inline-flex" onclick="if(!document.fullscreenElement){document.documentElement.requestFullscreen();}else{document.exitFullscreen();}" title="ملء الشاشة">
                            <i class="fas fa-expand-arrows-alt"></i>
                        </button>

                        <!-- User Profile Dropdown -->
                        <div class="dropdown">
                            <div class="lft-user-profile" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" role="button">
                                <div class="lft-user-avatar">
                                    {{ mb_substr(auth()->user()->name ?? 'م', 0, 1) }}
                                </div>
                                <div class="lft-user-info d-none d-md-flex">
                                    <span class="lft-user-name">{{ auth()->user()->name ?? 'المدير العام' }}</span>
                                    <span class="lft-user-role">مدير النظام</span>
                                </div>
                                <i class="fas fa-chevron-down font-size-xs text-muted mr-1"></i>
                            </div>
                            <div class="dropdown-menu dropdown-menu-right py-2 mt-2 shadow-sm border-0" style="border-radius: 12px; min-width: 200px;">
                                <div class="px-4 py-2 border-bottom mb-2">
                                    <div class="font-weight-bold text-dark">{{ auth()->user()->name ?? 'المدير العام' }}</div>
                                    <small class="text-muted">{{ auth()->user()->email ?? 'admin@leaderfortrans.com' }}</small>
                                </div>
                                <a class="dropdown-item py-2" href="{{ route('profile.edit') }}">
                                    <i class="far fa-user ml-2 text-primary"></i> {{ __('profile.edit') }}
                                </a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item py-2 text-danger" href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                    <i class="fas fa-sign-out-alt ml-2 text-danger"></i> {{ __('Logout') }}
                                </a>
                                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                    @csrf
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <!--end::Topbar Actions-->
            </div>
            <!--end::Container-->
        </div>
        <!--end::Header-->

        <!--begin::Content-->
        <main class="content d-flex flex-column flex-column-fluid" id="kt_content" tabindex="-1">
            @yield('content')
        </main>
        <!--end::Content-->

        <!--begin::Footer-->
        <div class="footer bg-white py-4 d-flex flex-lg-column" id="kt_footer">
            <!--begin::Container-->
            <div class="container-fluid d-flex flex-column flex-md-row align-items-center justify-content-between">
                <!--begin::Copyright-->
                <div class="text-dark order-2 order-md-1">
                    <span class="text-muted font-weight-bold mr-2">{{ date('Y') }} ©</span>
                    <a href="{{ route('main') }}" class="text-dark-75 text-hover-primary">LeaderForTrans</a>
                </div>
                <!--end::Copyright-->
                <!--begin::Nav-->
                <div class="nav nav-dark">
                </div>
                <!--end::Nav-->
            </div>
            <!--end::Container-->
        </div>
        <!--end::Footer-->
    </div>
    <!--end::Wrapper-->

</div>


