<div class="col-lg-12">
                                <div class="card card-outline-primary">
                                    <div class="card-header">
                                        <h4 class="m-b-0 text-white">Admin Dashboard</h4>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="card p-30">
                                                <div class="media">
                                                    <div class="media-left meida media-middle">
                                                        <span><i class="fa fa-home f-s-40"></i></span>
                                                    </div>
                                                    <div class="media-body media-text-right">
                                                        <h2><?php
                                                            $sql = "SELECT * FROM restaurant";
                                                            $result = mysqli_query($db, $sql);
                                                            $rws = mysqli_num_rows($result);
                                                            echo $rws;
                                                        ?></h2>
                                                        <p class="m-b-0">Restaurants</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="card p-30">
                                                <div class="media">
                                                    <div class="media-left meida media-middle">
                                                        <span><i class="fa fa-cutlery f-s-40"></i></span>
                                                    </div>
                                                    <div class="media-body media-text-right">
                                                        <h2><?php
                                                            $sql = "SELECT * FROM dishes";
                                                            $result = mysqli_query($db, $sql);
                                                            $rws = mysqli_num_rows($result);
                                                            echo $rws;
                                                        ?></h2>
                                                        <p class="m-b-0">Dishes</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="card p-30">
                                                <div class="media">
                                                    <div class="media-left meida media-middle">
                                                        <span><i class="fa fa-users f-s-40"></i></span>
                                                    </div>
                                                    <div class="media-body media-text-right">
                                                        <h2><?php
                                                            $sql = "SELECT * FROM users";
                                                            $result = mysqli_query($db, $sql);
                                                            $rws = mysqli_num_rows($result);
                                                            echo $rws;
                                                        ?></h2>
                                                        <p class="m-b-0">Users</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="card p-30">
                                                <div class="media">
                                                    <div class="media-left meida media-middle">
                                                        <span><i class="fa fa-shopping-cart f-s-40"></i></span>
                                                    </div>
                                                    <div class="media-body media-text-right">
                                                        <h2><?php
                                                            $sql = "SELECT * FROM users_orders";
                                                            $result = mysqli_query($db, $sql);
                                                            $rws = mysqli_num_rows($result);
                                                            echo $rws;
                                                        ?></h2>
                                                        <p class="m-b-0">Total Orders</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="card p-30">
                                                <div class="media">
                                                    <div class="media-left meida media-middle">
                                                        <span><i class="fa fa-th-large f-s-40"></i></span>
                                                    </div>
                                                    <div class="media-body media-text-right">
                                                        <h2><?php
                                                            $sql = "SELECT * FROM res_category";
                                                            $result = mysqli_query($db, $sql);
                                                            $rws = mysqli_num_rows($result);
                                                            echo $rws;
                                                        ?></h2>
                                                        <p class="m-b-0">Restro Categories</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="card p-30">
                                                <div class="media">
                                                    <div class="media-left meida media-middle">
                                                        <span><i class="fa fa-spinner f-s-40"></i></span>
                                                    </div>
                                                    <div class="media-body media-text-right">
                                                        <h2><?php
                                                            $sql = "SELECT * FROM users_orders WHERE status = 'in process'";
                                                            $result = mysqli_query($db, $sql);
                                                            $rws = mysqli_num_rows($result);
                                                            echo $rws;
                                                        ?></h2>
                                                        <p class="m-b-0">Processing Orders</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="card p-30">
                                                <div class="media">
                                                    <div class="media-left meida media-middle">
                                                        <span><i class="fa fa-check f-s-40"></i></span>
                                                    </div>
                                                    <div class="media-body media-text-right">
                                                        <h2><?php
                                                            $sql = "SELECT * FROM users_orders WHERE status = 'closed'";
                                                            $result = mysqli_query($db, $sql);
                                                            $rws = mysqli_num_rows($result);
                                                            echo $rws;
                                                        ?></h2>
                                                        <p class="m-b-0">Delivered Orders</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="card p-30">
                                                <div class="media">
                                                    <div class="media-left meida media-middle">
                                                        <span><i class="fa fa-times f-s-40"></i></span>
                                                    </div>
                                                    <div class="media-body media-text-right">
                                                        <h2><?php
                                                            $sql = "SELECT * FROM users_orders WHERE status = 'rejected'";
                                                            $result = mysqli_query($db, $sql);
                                                            $rws = mysqli_num_rows($result);
                                                            echo $rws;
                                                        ?></h2>
                                                        <p class="m-b-0">Cancelled Orders</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="card p-30">
                                                <div class="media">
                                                    <div class="media-left meida media-middle">
                                                        <span><i class="fa fa-inr f-s-40"></i></span> <!-- Changed from fa-usd to fa-inr -->
                                                    </div>
                                                    <div class="media-body media-text-right">
                                                        <h2><?php
                                                            $result = mysqli_query($db, "SELECT SUM(price) AS value_sum FROM users_orders WHERE status = 'closed'");
                                                            $row = mysqli_fetch_assoc($result);
                                                            $sum = $row['value_sum'];
                                                            echo $sum;
                                                        ?></h2>
                                                        <p class="m-b-0">Total Earnings</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>